<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\DepositSubmitted;
use App\Models\Setting;
use App\Models\WalletDeposit;
use App\Models\User;
use App\Support\WalletLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DepositController extends Controller
{
    public function store(Request $request)
    {
        $min = WalletLimits::minDeposit();

        $request->validate([
            'amount'          => 'required|numeric|min:' . $min . '|max:500000',
            'bank_name'       => 'required|string|max:120',
            'depositor_name'  => 'required|string|max:120',
            'reference_number'=> 'nullable|string|max:120',
            'slip'            => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ], [
            'amount.min' => 'The smallest deposit is ' . WalletLimits::money($min) . '.',
        ]);

        $path = null;
        if ($request->hasFile('slip')) {
            $ext  = $request->file('slip')->getClientOriginalExtension() ?: 'bin';
            $name = auth()->id() . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            // storeAs returns the path relative to the disk root (e.g. "deposits/123_xxx.jpg").
            // That's what asset('storage/' . $path) will serve after php artisan storage:link.
            $path = $request->file('slip')->storeAs('deposits', $name, 'public');
        }

        $deposit = WalletDeposit::create([
            'user_id'          => auth()->id(),
            'amount'           => $request->amount,
            'bank_name'        => $request->bank_name,
            'depositor_name'   => $request->depositor_name,
            'reference_number' => $request->reference_number,
            'slip_path'        => $path,
            'status'           => 'pending',
        ]);

        // Email admin (to configured support email, or all admins if no support email set)
        try {
            $adminEmail = Setting::get('general', 'support_email');
            if (!$adminEmail) {
                $adminEmail = User::where('is_admin', true)->value('email');
            }
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new DepositSubmitted($deposit));
            }
        } catch (\Throwable $e) {
            \Log::warning('Deposit admin email failed: ' . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => 'Deposit request submitted! We\'ll verify and credit your wallet within a few hours.',
                'deposit' => [
                    'id'         => $deposit->id,
                    'reference'  => $deposit->reference(),
                    'amount'     => (float) $deposit->amount,
                    'status'     => $deposit->status,
                    'created_at' => $deposit->created_at->toIso8601String(),
                ],
            ]);
        }

        return back()->with('success', 'Deposit request submitted! We\'ll verify and credit your wallet within a few hours.');
    }
}
