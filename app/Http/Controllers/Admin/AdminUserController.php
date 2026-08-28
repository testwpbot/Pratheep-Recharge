<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use App\Support\HistoryPeriod;
use App\Support\WalletLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $min = WalletLimits::minBalance();
        $filter = (string) $request->input('filter', 'customers');
        $search = trim((string) $request->input('q'));

        $query = User::query()
            ->with('wallet')
            ->withCount('orders')
            ->orderByDesc('id');

        if ($filter === 'customers') {
            $query->where('is_admin', false);
        } elseif ($filter === 'retailers') {
            $query->where('is_admin', false)->where('is_retailer', true);
        } elseif ($filter === 'admins') {
            $query->where('is_admin', true);
        } elseif ($filter === 'low') {
            $query->where('is_admin', false)
                ->whereHas('wallet', fn ($q) => $q->where('balance', '<', $min));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(30)->withQueryString();

        $stats = [
            'customers'    => User::where('is_admin', false)->count(),
            'retailers'    => User::where('is_admin', false)->where('is_retailer', true)->count(),
            'wallet_total' => (float) Wallet::query()
                ->whereHas('user', fn ($q) => $q->where('is_admin', false))
                ->sum('balance'),
            'low'          => Wallet::query()
                ->whereHas('user', fn ($q) => $q->where('is_admin', false))
                ->where('balance', '<', $min)
                ->count(),
        ];

        return view('admin.users.index', compact('users', 'filter', 'search', 'stats', 'min'));
    }

    public function create(): View
    {
        $min = WalletLimits::minDeposit();

        return view('admin.users.create', compact('min'));
    }

    public function store(Request $request): RedirectResponse
    {
        $min = WalletLimits::minDeposit();

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'            => ['required', 'string', 'max:15', 'regex:/^\+?[0-9]{9,15}$/', 'unique:users,phone'],
            'password'         => ['required', 'string', Password::defaults()],
            'is_retailer'      => ['sometimes', 'boolean'],
            'opening_balance'  => ['nullable', 'numeric', 'min:0', 'max:500000'],
        ], [
            'phone.regex' => 'Enter a valid phone number (e.g. 0771234567).',
        ]);

        $phone = $this->normalizePhone($data['phone']);
        if (User::where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'That phone number is already used.']);
        }

        $user = User::create([
            'name'              => $data['name'],
            'email'             => strtolower($data['email']),
            'phone'             => $phone,
            'password'          => $data['password'],
            'is_retailer'       => $request->boolean('is_retailer'),
            'email_verified_at' => now(),
        ]);

        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
        $opening = round((float) ($data['opening_balance'] ?? 0), 2);
        if ($opening >= 0.01) {
            app(WalletService::class)->adjust(
                $wallet,
                'set',
                $opening,
                $request->user(),
                'Opening wallet when admin created the account.'
            );
        }

        $hint = $opening < $min
            ? ' They still need at least LKR ' . number_format($min, 2) . ' in the wallet to recharge.'
            : '';

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', $user->name . ' was added.' . $hint);
    }

    public function show(Request $request, User $user): View
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
        $user->setRelation('wallet', $wallet);
        $period = HistoryPeriod::fromRequest($request);

        $txQuery = $wallet->transactions();
        $period->apply($txQuery);
        $transactions = $txQuery->latest('id')->paginate(20, ['*'], 'tx_page')->withQueryString();

        $orderQuery = $user->orders()->with('service');
        $period->apply($orderQuery, 'created_at', fn ($q) => $q->whereIn('status', ['pending', 'processing']));
        $orders = $orderQuery->latest()->limit(15)->get();

        $depQuery = $user->deposits();
        $period->apply($depQuery, 'created_at', fn ($q) => $q->where('status', 'pending'));
        $deposits = $depQuery->latest()->limit(10)->get();
        $min = WalletLimits::minBalance();
        $notice = WalletLimits::notice($user, $wallet);

        return view('admin.users.show', compact(
            'user', 'wallet', 'transactions', 'orders', 'deposits', 'min', 'notice', 'period'
        ));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->is_admin) {
            return back()->with('error', 'Admin accounts are changed in Settings → Admins.');
        }

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'    => ['required', 'string', 'max:15', 'regex:/^\+?[0-9]{9,15}$/'],
            'password' => ['nullable', 'string', 'min:8', 'max:120'],
        ], [
            'phone.regex' => 'Enter a valid phone number (e.g. 0771234567).',
        ]);

        $phone = $this->normalizePhone($data['phone']);
        if (User::where('phone', $phone)->where('id', '!=', $user->id)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'That phone number is already used.']);
        }

        $user->name = $data['name'];
        $user->email = strtolower($data['email']);
        $user->phone = $phone;
        $user->is_retailer = $request->boolean('is_retailer');
        $user->email_verified_at = $request->boolean('email_verified')
            ? ($user->email_verified_at ?: now())
            : null;

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return back()->with('success', 'Saved details for ' . $user->name . '.');
    }

    public function adjustWallet(Request $request, User $user, WalletService $wallets): RedirectResponse
    {
        $data = $request->validate([
            'mode'   => ['required', 'in:add,remove,set'],
            'amount' => ['required', 'numeric', 'min:0', 'max:500000'],
            'note'   => ['required', 'string', 'max:500'],
        ]);

        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        try {
            $result = $wallets->adjust(
                $wallet,
                $data['mode'],
                (float) $data['amount'],
                $request->user(),
                $data['note']
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $msg = 'Wallet updated for ' . $user->name . ': LKR '
            . number_format($result['before'], 2) . ' → LKR '
            . number_format($result['after'], 2) . '.';

        return back()->with('success', $msg);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->is_admin) {
            return back()->with('error', 'Admin accounts are removed in Settings → Admins.');
        }

        $name = $user->name;

        try {
            $user->delete();
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not delete this user: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', $name.' was deleted. Their wallet and orders were removed too.');
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);
        if (str_starts_with($phone, '0') && ! str_starts_with($phone, '+')) {
            $phone = '+94' . substr($phone, 1);
        }

        return $phone;
    }
}
