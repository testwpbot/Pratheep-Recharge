<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * DTH input is entered by the CUSTOMER in INR (the pack value). The wallet is
 * charged INR * rate (LKR) and the provider receives the INR pack value.
 *
 * Example: customer enters INR 500, rate 3.65
 *   wallet charge = 500 * 3.65 = LKR 1825
 *   provider gets = INR 500
 */
class DthInrInputTest extends TestCase
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

    public function test_customer_enters_inr_wallet_charged_lkr_provider_gets_inr(): void
    {
        Setting::set('general', 'dth_inr_rate', '3.65');
        $svc  = $this->dthService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        Http::fake(['*tmobiling.lk/*' => Http::response(['status' => 'success', 'transaction_id' => 'TM-1'], 200)]);

        // Customer enters INR 500 in the amount box.
        $res = $this->actingAs($user)->postJson(route('recharge.confirm'), [
            'service_id'     => $svc->id,
            'account_number' => '1234567890',
            'amount'         => 500,
        ]);
        $res->assertOk()->assertJsonPath('ok', true);

        $order = \App\Models\Order::first();

        // Wallet is charged INR * rate = 1825 LKR; provider receives the INR 500.
        $this->assertEquals(1825, (float) $order->amount);
        $this->assertEquals(3.65, $order->fxRate());
        $this->assertEquals(500, $order->providerAmount());
        $this->assertEquals(1825, $order->totalPaid());
        $this->assertEquals(10000 - 1825, (float) Wallet::where('user_id', $user->id)->value('balance'));

        // The outgoing provider request carried the INR pack value (500).
        Http::assertSent(function ($request) {
            return $request['method'] === 'recharge' && (string) $request['amount'] === '500';
        });

        $debit = WalletTransaction::where('transactable_id', $order->id)->where('type', 'debit')->first();
        $this->assertEquals(1825, (float) $debit->amount);
    }

    public function test_dth_minimum_is_checked_in_inr(): void
    {
        Setting::set('general', 'dth_inr_rate', '3.65');
        $svc  = $this->dthService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        // INR 9 is below the INR 10 minimum.
        $res = $this->actingAs($user)->postJson(route('recharge.confirm'), [
            'service_id'     => $svc->id,
            'account_number' => '1234567890',
            'amount'         => 9,
        ]);
        // 9 < 10 fails the base validation (min:10) OR the DTH INR minimum.
        $res->assertStatus(422);
        $this->assertEquals(0, \App\Models\Order::count());
    }

    /**
     * DTH is routed through Topup Mart (op codes 120-124). Topup Mart must be
     * sent the EXACT value the customer typed in the amount box (Topup Mart
     * treats it as LKR), NOT the converted LKR wallet charge. The customer's
     * wallet is still charged (typed x rate) LKR via our conversion.
     */
    public function test_topup_mart_dth_receives_inr_pack_value(): void
    {
        Setting::set('general', 'dth_inr_rate', '3.65');

        $cat = Category::create(['name' => 'DTH Recharge', 'slug' => 'dth', 'sort_order' => 1, 'is_active' => true]);
        $topup = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'k', 'is_active' => true,
        ]);
        // Videocon d2h on Topup Mart = op 124.
        $svc = Service::create([
            'provider_id' => $topup->id, 'category_id' => $cat->id,
            'op_code' => '124', 'name' => 'Videocon d2h', 'type' => 'dth',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);

        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        Http::fake(['*topupmart.online/api/v2/recharge.php' => Http::response([
            'status' => 'success', 'transaction_id' => 'TM-DTH-1', 'message' => 'OK',
        ], 200)]);

        // Customer types 500 in the amount box; wallet charged 500 * 3.65 = LKR 1825.
        $res = $this->actingAs($user)->postJson(route('recharge.confirm'), [
            'service_id'     => $svc->id,
            'account_number' => '1234567890',
            'amount'         => 500,
        ]);
        $res->assertOk()->assertJsonPath('ok', true);

        $order = \App\Models\Order::first();
        $this->assertEquals(1825, (float) $order->amount);       // LKR wallet charge
        $this->assertEquals(500, $order->providerAmount());      // exact typed value

        // Topup Mart must have received the exact typed value (500), with op 124.
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'topupmart.online')
                && (string) $request['amount'] === '500'
                && (string) $request['op_code'] === '124';
        });
    }
}
