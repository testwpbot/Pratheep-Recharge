<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Support\HistoryPeriod;
use App\Support\WalletLimits;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $user   = auth()->user();
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
        $period = HistoryPeriod::fromRequest($request);

        $depositQuery = WalletDeposit::where('user_id', $user->id);
        $period->apply($depositQuery, 'created_at', fn ($q) => $q->where('status', 'pending'));
        $deposits = $depositQuery
            ->latest()
            ->paginate(15, ['*'], 'dep_page')
            ->withQueryString();

        $txQuery = $wallet->transactions();
        $period->apply($txQuery);
        $transactions = $txQuery
            ->latest()
            ->paginate(15, ['*'], 'tx_page')
            ->withQueryString();

        $banks = \App\Models\BankAccount::active()->get();
        $general = Setting::forGroup('general');
        $minDeposit = WalletLimits::minDeposit();
        $walletNotice = WalletLimits::notice($user, $wallet);

        $pendingDepositCount = WalletDeposit::where('user_id', $user->id)->where('status', 'pending')->count();

        return view('dashboard.wallet', compact(
            'wallet', 'deposits', 'transactions', 'banks', 'general', 'minDeposit', 'walletNotice', 'period', 'pendingDepositCount'
        ));
    }
}
