<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Order;
use App\Models\Provider;
use App\Models\ProviderBalanceSnapshot;
use App\Models\Service;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\FundHealthService;
use App\Support\HistoryPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request, FundHealthService $funds): View
    {
        $period = HistoryPeriod::fromRequest($request);

        $todaySuccess = Order::query()->where('status', 'success');
        $period->apply($todaySuccess);

        $stats = [
            'users'      => User::where('is_admin', false)->count(),
            'services'   => Service::where('is_active', true)->count(),
            'providers'  => Provider::where('is_active', true)->count(),
            'orders'     => Order::count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'revenue'    => (clone $todaySuccess)->sum('amount'),
            'cashback'   => (clone $todaySuccess)->sum('profit'),
            'pending'    => Order::whereIn('status', ['pending', 'processing'])->count(),
            'complaints_open' => Complaint::whereIn('status', ['open', 'in_progress'])->count(),
        ];

        $recentOrderQuery = Order::with(['user', 'service', 'provider']);
        $period->apply($recentOrderQuery, 'created_at', fn ($q) => $q->whereIn('status', ['pending', 'processing']));
        $recentOrders = $recentOrderQuery->latest()->limit(15)->get();
        $providers = Provider::all();

        $health = $funds->overview(false);
        $byId = collect($health['providers'])->keyBy('id');

        $recentWalletQuery = WalletTransaction::with(['wallet.user']);
        $period->apply($recentWalletQuery);
        $recentWallet = $recentWalletQuery->latest('id')->limit(8)->get();
        $recentSnapQuery = ProviderBalanceSnapshot::with('provider');
        $period->apply($recentSnapQuery);
        $recentSnaps = $recentSnapQuery->latest('id')->limit(8)->get();

        return view('admin.dashboard.index', compact(
            'stats', 'recentOrders', 'providers', 'health', 'byId', 'recentWallet', 'recentSnaps', 'period'
        ));
    }
}
