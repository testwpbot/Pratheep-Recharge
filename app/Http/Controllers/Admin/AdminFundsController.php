<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Provider;
use App\Models\ProviderBalanceSnapshot;
use App\Models\Setting;
use App\Models\WalletTransaction;
use App\Services\FundHealthService;
use App\Support\HistoryPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFundsController extends Controller
{
    public function __construct(protected FundHealthService $funds) {}

    public function index(Request $request): View
    {
        $health = $this->funds->check(fresh: false, persist: true, alert: true);

        $providerId = $request->integer('provider_id') ?: null;
        $tab = $request->input('tab', 'snapshots');
        if (! in_array($tab, ['snapshots', 'wallets', 'orders'], true)) {
            $tab = 'snapshots';
        }

        $period = HistoryPeriod::fromRequest($request);

        $snapQuery = ProviderBalanceSnapshot::with('provider')
            ->when($providerId, fn ($q) => $q->where('provider_id', $providerId));
        $period->apply($snapQuery);
        $snapshots = $snapQuery
            ->latest('id')
            ->paginate(20, ['*'], 'snap_page')
            ->withQueryString();

        $walletQuery = WalletTransaction::with(['wallet.user']);
        $period->apply($walletQuery);
        $walletTx = $walletQuery
            ->latest('id')
            ->paginate(20, ['*'], 'wallet_page')
            ->withQueryString();

        $orderQuery = Order::with(['user', 'service', 'provider'])
            ->when($providerId, fn ($q) => $q->where('provider_id', $providerId));
        $period->apply($orderQuery, 'created_at', fn ($q) => $q->whereIn('status', ['pending', 'processing']));
        $orders = $orderQuery
            ->latest('id')
            ->paginate(20, ['*'], 'order_page')
            ->withQueryString();

        $providers = Provider::orderBy('name')->get();
        $settings = $health['settings'];

        return view('admin.funds.index', compact(
            'health', 'snapshots', 'walletTx', 'orders',
            'providers', 'providerId', 'tab', 'settings', 'period'
        ));
    }

    public function refresh(Request $request): RedirectResponse
    {
        $this->funds->check(fresh: true, persist: true, alert: true);

        return redirect()
            ->route('admin.funds.index')
            ->with('status', 'Checked again. Provider wallets were compared to customer wallets.');
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'alerts_enabled' => 'nullable|boolean',
            'alert_email'    => 'nullable|email|max:255',
            'cooldown_hours' => 'required|integer|min:1|max:48',
            'min_inr'        => 'required|numeric|min:0|max:10000000',
            'inr_to_lkr'     => 'nullable|numeric|min:0|max:100',
        ]);

        Setting::set('funds', 'alerts_enabled', $request->boolean('alerts_enabled') ? '1' : '0');
        Setting::set('funds', 'alert_email', $data['alert_email'] ?? '');
        Setting::set('funds', 'cooldown_hours', (string) $data['cooldown_hours']);
        Setting::set('funds', 'min_inr', (string) $data['min_inr']);
        Setting::set('funds', 'inr_to_lkr', (string) ($data['inr_to_lkr'] ?? 0));

        return back()->with('status', 'Email alert settings saved.');
    }
}
