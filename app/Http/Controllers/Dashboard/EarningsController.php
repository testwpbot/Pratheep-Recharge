<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Support\HistoryPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    /**
     * Earnings page — cashback history ONLY.
     * Customers see what they've earned from successful recharges,
     * filterable by date range.
     */
    public function index(Request $request): View
    {
        $user   = auth()->user();
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        $from = $request->query('from');
        $to   = $request->query('to');

        $q = $wallet->transactions()
            ->with('transactable')
            ->where('type', 'cashback')
            ->latest();

        if ($from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $q->whereDate('created_at', '<=', $to);
        }

        $earnings = $q->paginate(20)->appends($request->query());

        $totalEarned   = (float) $wallet->transactions()->where('type', 'cashback')->sum('amount');
        $thisMonth     = (float) $wallet->transactions()->where('type', 'cashback')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        $filteredTotal = (float) (clone $q)->sum('amount');

        return view('dashboard.earnings', compact(
            'wallet', 'earnings', 'period',
            'totalEarned', 'thisMonth', 'filteredTotal'
        ));
    }
}
