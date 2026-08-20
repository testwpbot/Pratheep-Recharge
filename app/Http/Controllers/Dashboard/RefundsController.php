<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
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

        $from = $request->query('from');
        $to   = $request->query('to');

        $q = $wallet->transactions()
            ->with('transactable')
            ->where('type', 'refund')
            ->latest();

        if ($from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $q->whereDate('created_at', '<=', $to);
        }

        $refunds = $q->paginate(20)->appends($request->query());

        $totalRefunded = (float) $wallet->transactions()->where('type', 'refund')->sum('amount');
        $thisMonth     = (float) $wallet->transactions()->where('type', 'refund')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        $filteredTotal = (float) (clone $q)->sum('amount');

        return view('dashboard.refunds', compact(
            'wallet', 'refunds', 'from', 'to',
            'totalRefunded', 'thisMonth', 'filteredTotal'
        ));
    }
}
