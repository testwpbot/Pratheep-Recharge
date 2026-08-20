<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Services\ProviderFactory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users'      => User::where('is_admin', false)->count(),
            'services'   => Service::where('is_active', true)->count(),
            'providers'  => Provider::where('is_active', true)->count(),
            'orders'     => Order::count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'revenue'    => Order::where('status', 'success')->sum('amount'),
            'cashback'   => Order::where('status', 'success')->sum('profit'),
            'pending'    => Order::whereIn('status', ['pending', 'processing'])->count(),
            'complaints_open' => Complaint::whereIn('status', ['open', 'in_progress'])->count(),
        ];

        $recentOrders = Order::with(['user', 'service', 'provider'])->latest()->limit(15)->get();
        $providers = Provider::all();

        return view('admin.dashboard.index', compact('stats', 'recentOrders', 'providers'));
    }
}
