<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OrderService;
use App\Services\Providers\TMobiling;
use App\Services\ServiceImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TMobilingProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function seedTMobiling(): array
    {
        $mobile = Category::firstOrCreate(['slug' => 'mobile'], [
            'name' => 'Mobile Reload', 'icon' => 'phone-menu', 'sort_order' => 1, 'is_active' => true,
        ]);
        $utility = Category::firstOrCreate(['slug' => 'utility'], [
            'name' => 'Utility Bills', 'icon' => 'bolt', 'sort_order' => 3, 'is_active' => true,
        ]);

        $provider = Provider::updateOrCreate(['slug' => 'tmobiling'], [
            'name' => 'TMobiling',
            'country' => 'LK',
            'api_class' => 'tmobiling',
            'base_url' => TMobiling::DEFAULT_BASE_URL,
            'api_key' => 'tmobi-test-key',
            'is_active' => true,
        ]);

        $dialog = Service::create([
            'provider_id' => $provider->id,
            'category_id' => $mobile->id,
            'op_code' => '1',
            'name' => 'Dialog Prepaid',
            'type' => 'prepaid',
            'profit' => 0,
            'profit_type' => 'FLAT',
            'is_active' => true,
        ]);

        $ceb = Service::create([
            'provider_id' => $provider->id,
            'category_id' => $utility->id,
            'op_code' => '29',
            'name' => 'CEB Electricity',
            'type' => 'utility',
            'profit' => 0,
            'profit_type' => 'FLAT',
            'is_active' => true,
            'meta' => ['bbps' => true, 'catalog_key' => 'ceb'],
        ]);

        return compact('provider', 'dialog', 'ceb', 'mobile', 'utility');
    }

    public function test_catalog_has_documented_operators(): void
    {
        $items = (new TMobiling())->fetchServices();
        $ops = collect($items)->pluck('op_code');
        $this->assertTrue($ops->contains('1'));
        $this->assertTrue($ops->contains('29'));
        $this->assertTrue($ops->contains('23'));
        $this->assertGreaterThan(20, $items);
        $ceb = collect($items)->firstWhere('op_code', '29');
        $this->assertTrue($ceb['bbps']);
    }

    public function test_import_creates_services(): void
    {
        $ctx = $this->seedTMobiling();
        $result = (new ServiceImporter())->importFromProvider($ctx['provider']);
        $this->assertGreaterThan(10, $result['imported'] + $result['skipped']);
        $this->assertDatabaseHas('services', [
            'provider_id' => $ctx['provider']->id,
            'op_code' => '4',
            'name' => 'Hutch Prepaid',
        ]);
    }

    public function test_recharge_success_maps_txn_id(): void
    {
        $ctx = $this->seedTMobiling();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);

        Http::fake([
            '*tmobiling.lk/livenew/apis/api_request*' => Http::response([
                'status' => 'success',
                'txn_id' => 'TMX-99',
                'message' => 'Recharge Accepted',
                'recharge_status' => 'success',
                'balance' => '5000',
                'auth_code' => 'OK1',
            ], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['dialog']->id, '0771234567', 100);

        $this->assertSame('success', $order->status);
        $this->assertSame('TMX-99', $order->provider_txn_id);
        $this->assertTrue($order->provider->isTMobiling());
    }

    public function test_recharge_pending_stays_pending(): void
    {
        $ctx = $this->seedTMobiling();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);

        Http::fake([
            '*tmobiling.lk/livenew/apis/api_request*' => Http::response([
                'status' => 'success',
                'txn_id' => 'TMX-1',
                'message' => 'Recharge Accepted',
                'recharge_status' => 'pending',
                'balance' => '5000',
                'auth_code' => '',
            ], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['dialog']->id, '0771234567', 100);
        $this->assertSame('pending', $order->status);
        $this->assertSame('TMX-1', $order->provider_txn_id);
    }

    public function test_recharge_failed_refunds_wallet(): void
    {
        $ctx = $this->seedTMobiling();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);

        Http::fake([
            '*tmobiling.lk/livenew/apis/api_request*' => Http::response([
                'status' => 'failed',
                'message' => 'Your recharge failed. please try again',
                'recharge_status' => 'failed',
                'mobile_number' => '0771234567',
                'amount' => '100',
                'your_reference' => 'x',
                'txn_id' => 'TMX-fail',
            ], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['dialog']->id, '0771234567', 100);
        $this->assertSame('refunded', $order->status);
        $this->assertEquals(1000.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_bbps_bill_sends_from_bbps_flag(): void
    {
        $ctx = $this->seedTMobiling();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);

        Http::fake([
            '*tmobiling.lk/livenew/apis/api_request*' => Http::response([
                'status' => 'success',
                'txn_id' => 'BILL-1',
                'recharge_status' => 'success',
                'message' => 'ok',
            ], 200),
        ]);

        app(OrderService::class)->placeOrder(
            $user,
            $ctx['ceb']->id,
            '12121212',
            100,
            '0765432198'
        );

        Http::assertSent(function ($request) {
            $url = $request->url();

            return str_contains($url, 'method=recharge')
                && str_contains($url, 'from_bbps=1')
                && str_contains($url, 'operator=29')
                && str_contains($url, 'ref_no=0765432198');
        });
    }

    public function test_status_check_success(): void
    {
        $ctx = $this->seedTMobiling();
        $user = User::factory()->create();
        $order = Order::create([
            'reference' => 'HPR-TEST-TM1',
            'user_id' => $user->id,
            'service_id' => $ctx['dialog']->id,
            'provider_id' => $ctx['provider']->id,
            'account_number' => '0771234567',
            'amount' => 100,
            'profit' => 0,
            'status' => 'pending',
            'provider_status' => 'pending',
        ]);

        Http::fake([
            '*tmobiling.lk/livenew/apis/api_request*' => Http::response([
                'recharge_session' => 'abc',
                'result' => 'success',
                'reason' => 'Transaction Success',
                'op_code' => 'OK1',
                'txn_id' => 'TMX-9',
                'status' => 'success',
            ], 200),
        ]);

        $resp = (new TMobiling($ctx['provider']->base_url, $ctx['provider']->api_key))->checkStatus($order);
        $this->assertSame('success', $resp['status']);
        $this->assertSame('TMX-9', $resp['transaction_id']);
    }

    public function test_balance_reads_message_field(): void
    {
        Http::fake([
            '*tmobiling.lk/livenew/apis/api_request*' => Http::response([
                'status' => 'success',
                'message' => '1,970.10',
            ], 200),
        ]);

        $bal = (new TMobiling(TMobiling::DEFAULT_BASE_URL, 'k'))->balance();
        $this->assertEquals(1970.10, $bal);
    }

    public function test_callback_rechecks_status_and_marks_success(): void
    {
        $ctx = $this->seedTMobiling();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);
        $order = Order::create([
            'reference' => 'HPR-CB-1',
            'user_id' => $user->id,
            'service_id' => $ctx['dialog']->id,
            'provider_id' => $ctx['provider']->id,
            'account_number' => '0771234567',
            'amount' => 80,
            'profit' => 0,
            'status' => 'pending',
            'provider_status' => 'pending',
        ]);

        Http::fake([
            '*tmobiling.lk/livenew/apis/api_request*' => Http::response([
                'result' => 'success',
                'reason' => 'Transaction Success',
                'txn_id' => 'TMX-CB',
                'status' => 'success',
            ], 200),
        ]);

        $this->get('/webhooks/tmobiling?status=success&reference=HPR-CB-1&txn_id=TMX-CB')
            ->assertOk()
            ->assertSee('ok');

        $this->assertSame('success', $order->fresh()->status);
        $this->assertSame('TMX-CB', $order->fresh()->provider_txn_id);
    }
}
