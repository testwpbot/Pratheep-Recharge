<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SriLankanBanks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $smtp = Setting::forGroup('smtp');
        $general = Setting::forGroup('general');
        $seo = Setting::forGroup('seo');
        $banks = BankAccount::query()->orderBy('sort_order')->orderBy('id')->get();
        $bankCatalog = SriLankanBanks::all();
        $isMainAdmin = auth()->user()?->isMainAdmin() ?? false;
        $admins = $isMainAdmin
            ? User::query()
                ->where('is_admin', true)
                ->orderByRaw("CASE WHEN admin_role = 'main' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get()
            : collect();

        return view('admin.settings.index', compact(
            'smtp', 'general', 'seo', 'banks', 'bankCatalog', 'admins', 'isMainAdmin'
        ));
    }

    public function saveSmtp(Request $request)
    {
        $data = $request->validate([
            'host'         => 'required|string|max:255',
            'port'         => 'required|integer|min:1|max:65535',
            'username'     => 'nullable|string|max:255',
            'password'     => 'nullable|string|max:500',
            'encryption'   => 'nullable|in:tls,ssl,none',
            'from_address' => 'required|email|max:255',
            'from_name'    => 'required|string|max:120',
        ]);

        foreach ($data as $k => $v) {
            if ($k === 'password' && $v === '') continue; // don't overwrite with blank
            Setting::set('smtp', $k, $v);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'SMTP settings saved.']);
        }
        return back()->with('success', 'SMTP settings saved.');
    }

    public function saveSeo(Request $request)
    {
        $data = $request->validate([
            'meta_title'              => 'nullable|string|max:70',
            'meta_description'        => 'nullable|string|max:180',
            'meta_keywords'           => 'nullable|string|max:255',
            'og_title'                => 'nullable|string|max:70',
            'og_description'          => 'nullable|string|max:180',
            'og_image_url'            => 'nullable|url|max:500',
            'og_image'                => 'nullable|image|max:2048',
            'robots'                  => 'nullable|in:index,noindex',
            'google_site_verification'=> 'nullable|string|max:120',
        ]);

        foreach (['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'og_image_url', 'robots', 'google_site_verification'] as $k) {
            if (array_key_exists($k, $data)) {
                Setting::set('seo', $k, (string) ($data[$k] ?? ''));
            }
        }

        if ($request->hasFile('og_image')) {
            $dir = public_path('uploads/seo');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $name = 'og-' . time() . '.' . $request->file('og_image')->getClientOriginalExtension();
            $request->file('og_image')->move($dir, $name);
            Setting::set('seo', 'og_image_path', 'uploads/seo/' . $name);
        }

        return redirect()->route('admin.settings.index', ['tab' => 'seo'])
            ->with('success', 'SEO settings saved.');
    }

    public function storeBank(Request $request): RedirectResponse
    {
        $data = $this->validateBank($request);
        $data['sort_order'] = (int) BankAccount::max('sort_order') + 1;
        $data['is_active'] = true;
        $account = BankAccount::create($data);
        $this->storeBankLogo($request, $account);

        return redirect()->route('admin.settings.index', ['tab' => 'bank'])
            ->with('success', 'Bank account added.');
    }

    public function updateBank(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $data = $this->validateBank($request);
        $data['is_active'] = $request->boolean('is_active', true);
        $bankAccount->fill($data)->save();
        $this->storeBankLogo($request, $bankAccount);

        return redirect()->route('admin.settings.index', ['tab' => 'bank'])
            ->with('success', 'Bank account updated.');
    }

    public function destroyBank(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        return redirect()->route('admin.settings.index', ['tab' => 'bank'])
            ->with('success', 'Bank account removed.');
    }

    protected function validateBank(Request $request): array
    {
        $data = $request->validate([
            'bank_slug'    => 'required|string|max:80',
            'bank_name'    => 'required|string|max:160',
            'account_name' => 'required|string|max:160',
            'account_no'   => 'required|string|max:80',
            'branch'       => 'nullable|string|max:160',
            'instructions' => 'nullable|string|max:2000',
            'logo_url'     => 'nullable|url|max:500',
            'logo'         => 'nullable|image|max:2048',
        ]);
        unset($data['logo']);

        $cat = SriLankanBanks::find($data['bank_slug']);
        if ($cat && $data['bank_slug'] !== 'custom' && empty($data['bank_name'])) {
            $data['bank_name'] = $cat['name'];
        }

        return $data;
    }

    protected function storeBankLogo(Request $request, BankAccount $account): void
    {
        if (! $request->hasFile('logo')) {
            return;
        }
        $dir = public_path('uploads/banks');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'bank-' . $account->id . '-' . time() . '.' . $request->file('logo')->getClientOriginalExtension();
        $request->file('logo')->move($dir, $name);
        $account->forceFill(['logo_path' => 'uploads/banks/' . $name])->save();
    }

    public function saveGeneral(Request $request)
    {
        $data = $request->validate([
            'site_name'       => 'required|string|max:120',
            'support_email'   => 'nullable|email|max:255',
            'support_phone'   => 'nullable|string|max:40',
            'deposit_note'    => 'nullable|string|max:2000',
        ]);

        foreach ($data as $k => $v) {
            Setting::set('general', $k, $v);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'General settings saved.']);
        }
        return back()->with('success', 'General settings saved.');
    }

    /**
     * Send a test email to verify SMTP works.
     */
    public function testSmtp(Request $request)
    {
        $request->validate(['to' => 'required|email']);
        try {
            // First apply the form values so test uses the latest (even if unsaved in DB)
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host'       => $request->host,
                'mail.mailers.smtp.port'       => (int) $request->port,
                'mail.mailers.smtp.username'   => $request->username,
                'mail.mailers.smtp.password'   => $request->password ?: config('mail.mailers.smtp.password'),
                'mail.mailers.smtp.encryption' => ($request->encryption ?? 'tls') === 'none' ? null : ($request->encryption ?? 'tls'),
                'mail.from.address'            => $request->from_address,
                'mail.from.name'               => $request->from_name,
            ]);

            Mail::raw('This is a test email from Happy Pratheep Recharge. If you received this, SMTP is configured correctly bro! ✅', function ($msg) use ($request) {
                $msg->to($request->to)->subject('SMTP Test — Happy Pratheep Recharge');
            });

            return response()->json(['ok' => true, 'message' => 'Test email sent to ' . $request->to]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'SMTP error: ' . $e->getMessage()], 500);
        }
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        $this->assertMainAdmin();

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'phone'    => ['required', 'string', 'max:15', 'regex:/^\+?[0-9]{9,15}$/'],
            'password' => 'nullable|string|min:8|max:120',
            'role'     => 'required|in:main,admin',
        ], [
            'phone.regex' => 'Enter a valid phone number (e.g. 0771234567).',
        ]);

        $email = strtolower($data['email']);
        $phone = $this->normalizePhone($data['phone']);
        $existing = User::where('email', $email)->first();

        if ($existing && $existing->is_admin) {
            return $this->adminsBack()->with('error', $existing->name . ' is already an admin.');
        }

        if (! $existing && User::where('phone', $phone)->exists()) {
            return $this->adminsBack()->with('error', 'That phone number is already used by another account.');
        }

        if ($existing && $existing->phone !== $phone && User::where('phone', $phone)->where('id', '!=', $existing->id)->exists()) {
            return $this->adminsBack()->with('error', 'That phone number is already used by another account.');
        }

        if (! $existing && empty($data['password'])) {
            return $this->adminsBack()->with('error', 'Set a password for this new admin.');
        }

        $user = $existing ?: new User;
        $user->name = $data['name'];
        $user->email = $email;
        $user->phone = $phone;
        $user->is_admin = true;
        $user->admin_role = $data['role'];
        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
        }
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        Wallet::firstOrCreate(['user_id' => $user->id]);

        $msg = $existing
            ? $user->name . ' is now a ' . $user->adminRoleLabel() . '.'
            : 'Added ' . $user->name . ' as ' . $user->adminRoleLabel() . '.';

        return $this->adminsBack()->with('success', $msg);
    }

    public function updateAdmin(Request $request, User $user): RedirectResponse
    {
        $this->assertMainAdmin();

        $data = $request->validate([
            'role' => 'required|in:main,admin',
        ]);

        if (! $user->is_admin) {
            return $this->adminsBack()->with('error', 'That person is not an admin.');
        }

        if ($user->isMainAdmin() && $data['role'] !== User::ADMIN_ROLE_MAIN && $this->mainAdminCount() <= 1) {
            return $this->adminsBack()->with('error', 'Keep at least one main admin.');
        }

        $user->forceFill(['admin_role' => $data['role']])->save();

        return $this->adminsBack()->with('success', $user->name . ' is now ' . $user->adminRoleLabel() . '.');
    }

    public function destroyAdmin(Request $request, User $user): RedirectResponse
    {
        $this->assertMainAdmin();

        if (! $user->is_admin) {
            return $this->adminsBack()->with('error', 'That person is not an admin.');
        }

        if ($user->id === auth()->id()) {
            return $this->adminsBack()->with('error', 'You cannot remove your own admin access.');
        }

        if ($user->isMainAdmin() && $this->mainAdminCount() <= 1) {
            return $this->adminsBack()->with('error', 'Keep at least one main admin.');
        }

        $user->forceFill([
            'is_admin'   => false,
            'admin_role' => null,
        ])->save();

        return $this->adminsBack()->with('success', $user->name . ' can no longer open the admin panel.');
    }

    protected function assertMainAdmin(): void
    {
        abort_unless(auth()->user()?->isMainAdmin(), 403, 'Only a main admin can manage admins.');
    }

    protected function mainAdminCount(): int
    {
        return User::query()
            ->where('is_admin', true)
            ->where('admin_role', User::ADMIN_ROLE_MAIN)
            ->count();
    }

    protected function adminsBack(): RedirectResponse
    {
        return redirect()->route('admin.settings.index', ['tab' => 'admins']);
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
