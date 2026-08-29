<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * DTH recharges are priced in INR: the customer enters the INR pack value,
 * which is sent to the provider unchanged, but the LKR wallet is charged
 * INR x the admin rate (general.dth_inr_rate, default 3.65).
 */
class DthFxConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function dthService(): Service
    {
        $cat = Category::create(['name' => 'DTH Recharge', 'slug' => 'dth', 'sort_order' => 1, 'is_active' => true]);
        $p = Provider::create([
            'name' => 'TMobiling', 'slug' => 'tmobiling', 'country' => 'LK',
            'api_class' => 'tmobiling', 'base_url' => 'https://www.tmobiling.lk/livenew/apis/api_request',
            'api_key' => 'k', 'is_active' => true,
        ]);

        return Service::create([
            'provider_id' => $p->id, 'category_id' => $cat->id,
            'op_code' => '20', 'name' => 'Airtel DTH', 'type' => 'dth',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
    }

    public function test_default_rate_is_365(): void
    {
        $this->assertEquals(3.65, Setting::dthInrRate());
    }

    public function test_wallet_charged_inr_times_rate(): void
    {
        Setting::set('general', 'dth_inr_rate', '3.65');
        $svc = $this->dthService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        Http::fake(['*tmobiling.lk/*' => Http::response(['status' => 'success', 'transaction_id' => 'TM-1'], 200)]);

        // Customer enters INR 500.
        $order = app(OrderService::class)->placeOrder($user, $svc->id, '1234567890', 500);

        // Provider still gets 500 (INR pack value) stored as amount.
        $this->assertEquals(500, (float) $order->amount);
        $this->assertEquals(3.65, $order->fxRate());
        // Wallet charged 500 * 3.65 = 1825.
        $this->assertEquals(1825, $order->totalPaid());
        $this->assertEquals(10000 - 1825, (float) Wallet::where('user_id', $user->id)->value('balance'));

        $debit = WalletTransaction::where('transactable_id', $order->id)->where('type', 'debit')->first();
        $this->assertEquals(1825, (float) $debit->amount);
    }

    public function test_admin_rate_change_applies_to_new_orders_only(): void
    {
        $svc = $this->dthService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 50000]);
        Http::fake(['*tmobiling.lk/*' => Http::response(['status' => 'success', 'transaction_id' => 'TM-2'], 200)]);

        Setting::set('general', 'dth_inr_rate', '4.00');
        $order = app(OrderService::class)->placeOrder($user, $svc->id, '1234567890', 100);
        $this->assertEquals(4.0, $order->fxRate());
        $this->assertEquals(400, $order->totalPaid());

        // Changing the rate later must not alter the stored rate on the order.
        Setting::set('general', 'dth_inr_rate', '5.00');
        $order->refresh();
        $this->assertEquals(4.0, $order->fxRate());
        $this->assertEquals(400, $order->totalPaid());
    }

    public function test_non_dth_service_has_rate_one(): void
    {
        $cat = Category::create(['name' => 'Mobile', 'slug' => 'mobile', 'sort_order' => 2, 'is_active' => true]);
        $p = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'k', 'is_active' => true,
        ]);
        $svc = Service::create([
            'provider_id' => $p->id, 'category_id' => $cat->id,
            'op_code' => '181', 'name' => 'Dialog', 'type' => 'prepaid',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);

        $this->assertFalse($svc->isDth());
        $this->assertEquals(1.0, $svc->fxRate());
    }
}
