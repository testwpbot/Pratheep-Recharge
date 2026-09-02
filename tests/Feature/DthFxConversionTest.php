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
 * Internal contract of OrderService::placeOrder() for DTH.
 *
 * The controller converts the customer's INR input to LKR before calling
 * placeOrder() (see DthInrInputTest for the INR-input HTTP flow). placeOrder()
 * itself always receives the LKR wallet charge in `amount`; DTH packs are
 * priced in INR, so the provider is credited amount / rate
 * (general.dth_inr_rate = LKR per 1 INR, default 3.65).
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
            'op_code' => '20', 'name' => 'Sun Direct', 'type' => 'dth',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
    }

    public function test_default_rate_is_365(): void
    {
        $this->assertEquals(3.65, Setting::dthInrRate());
    }

    public function test_wallet_charged_lkr_provider_credited_inr(): void
    {
        Setting::set('general', 'dth_inr_rate', '3.65');
        $svc = $this->dthService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        Http::fake(['*tmobiling.lk/*' => Http::response(['status' => 'success', 'transaction_id' => 'TM-1'], 200)]);

        // placeOrder() receives the LKR wallet charge (the controller already
        // converted the customer's INR 500 input to 500 * 3.65 = 1825).
        $order = app(OrderService::class)->placeOrder($user, $svc->id, '1234567890', 1825);

        // amount is the LKR the customer paid; wallet charged exactly that.
        $this->assertEquals(1825, (float) $order->amount);
        $this->assertEquals(3.65, $order->fxRate());
        $this->assertEquals(1825, $order->totalPaid());
        $this->assertEquals(10000 - 1825, (float) Wallet::where('user_id', $user->id)->value('balance'));

        // Provider is credited the INR equivalent: 1825 / 3.65 = 500.
        $this->assertEquals(500, $order->providerAmount());

        // And the outgoing request carried 500, not 1825.
        Http::assertSent(function ($request) {
            return $request['method'] === 'recharge' && (string) $request['amount'] === '500';
        });

        $debit = WalletTransaction::where('transactable_id', $order->id)->where('type', 'debit')->first();
        $this->assertEquals(1825, (float) $debit->amount);
    }

    public function test_rate_stored_per_order_is_immutable(): void
    {
        $svc = $this->dthService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 50000]);
        Http::fake(['*tmobiling.lk/*' => Http::response(['status' => 'success', 'transaction_id' => 'TM-2'], 200)]);

        Setting::set('general', 'dth_inr_rate', '4.00');
        $order = app(OrderService::class)->placeOrder($user, $svc->id, '1234567890', 400);
        $this->assertEquals(4.0, $order->fxRate());
        $this->assertEquals(100, $order->providerAmount()); // 400 / 4

        // Changing the rate later must not alter this order.
        Setting::set('general', 'dth_inr_rate', '5.00');
        $order->refresh();
        $this->assertEquals(4.0, $order->fxRate());
        $this->assertEquals(100, $order->providerAmount());
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
        $this->assertEquals(250.0, $svc->providerAmountFor(250));
    }
}
