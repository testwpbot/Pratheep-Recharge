<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Support\WalletLimits;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(): View
    {
        $user   = auth()->user();
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        $deposits = WalletDeposit::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $transactions = $wallet->transactions()
            ->latest()
            ->limit(30)
            ->get();

        $banks = \App\Models\BankAccount::active()->get();
        $general = Setting::forGroup('general');
        $minDeposit = WalletLimits::minDeposit();
        $walletNotice = WalletLimits::notice($user, $wallet);

        return view('dashboard.wallet', compact(
            'wallet', 'deposits', 'transactions', 'banks', 'general', 'minDeposit', 'walletNotice'
        ));
    }
}
