<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Support\HistoryPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundsController extends Controller
{
    /**
     * Refunds page — refund transactions only (money returned to the wallet
     * from failed recharges, declined orders, or admin adjustments).
     */
    public function index(Request $request): View
    {
        $user   = auth()->user();
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        $period = HistoryPeriod::fromRequest($request);

        $q = $wallet->transactions()
            ->with('transactable')
            ->where('type', 'refund')
            ->latest();
        $period->apply($q);

        $refunds = $q->paginate(20)->appends($request->query());

        $totalRefunded = (float) $wallet->transactions()->where('type', 'refund')->sum('amount');
        $thisMonth     = (float) $wallet->transactions()->where('type', 'refund')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        $filteredTotal = (float) (clone $q)->sum('amount');

        return view('dashboard.refunds', compact(
            'wallet', 'refunds', 'period',
            'totalRefunded', 'thisMonth', 'filteredTotal'
        ));
    }
}
