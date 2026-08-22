<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Provider;
use App\Models\ProviderBalanceSnapshot;
use App\Models\Setting;
use App\Models\WalletTransaction;
use App\Services\FundHealthService;
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

        $snapshots = ProviderBalanceSnapshot::with('provider')
            ->when($providerId, fn ($q) => $q->where('provider_id', $providerId))
            ->latest('id')
            ->paginate(20, ['*'], 'snap_page')
            ->withQueryString();

        $walletTx = WalletTransaction::with(['wallet.user'])
            ->latest('id')
            ->paginate(20, ['*'], 'wallet_page')
            ->withQueryString();

        $orders = Order::with(['user', 'service', 'provider'])
            ->when($providerId, fn ($q) => $q->where('provider_id', $providerId))
            ->latest('id')
            ->paginate(20, ['*'], 'order_page')
            ->withQueryString();

        $providers = Provider::orderBy('name')->get();
        $settings = $health['settings'];

        return view('admin.funds.index', compact(
            'health', 'snapshots', 'walletTx', 'orders',
            'providers', 'providerId', 'tab', 'settings'
        ));
    }

    public function refresh(Request $request): RedirectResponse
    {
        $this->funds->check(fresh: true, persist: true, alert: true);

        return redirect()
            ->route('admin.funds.index')
            ->with('status', 'Provider balances refreshed and compared to customer wallets.');
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

        return back()->with('status', 'Funds alert settings saved.');
    }
}
