<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OrderService;
use App\Services\Providers\HappyRechargeCenter;
use App\Services\ServiceImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HappyRechargeDthTest extends TestCase
{
    use RefreshDatabase;

    protected function seedProviders(): array
    {
        $dth = Category::firstOrCreate(['slug' => 'dth'], [
            'name' => 'DTH Recharge', 'icon' => 'tv-card', 'sort_order' => 6, 'is_active' => true,
        ]);

        $topup = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'tm-test-key', 'is_active' => true,
        ]);
        $hrc = Provider::create([
            'name' => 'Happy Recharge Center', 'slug' => 'happy-recharge-center', 'country' => 'IN',
            'api_class' => 'happy_recharge_center',
            'base_url' => 'http://happyrechargecenter.com/RechargeApi',
            'api_key' => '334d7b447e9459fcbafe9441a', 'is_active' => true,
        ]);

        $tmDth = Service::create([
            'provider_id' => $topup->id, 'category_id' => $dth->id,
            'op_code' => '120', 'name' => 'Airtel DTH', 'type' => 'dth',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => false,
            'meta' => ['failover_op_code' => '120'],
        ]);
        $hrcDth = Service::create([
            'provider_id' => $hrc->id, 'category_id' => $dth->id,
            'op_code' => '20', 'name' => 'Airtel DTH', 'type' => 'dth',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
            'meta' => ['failover_op_code' => '120'],
        ]);

        return compact('dth', 'topup', 'hrc', 'tmDth', 'hrcDth');
    }

    public function test_hrc_catalog_is_dth_only(): void
    {
        $items = (new HappyRechargeCenter())->fetchServices();
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertSame('dth', $item['type']);
            $this->assertSame('dth', $item['category_slug']);
            $this->assertArrayHasKey('failover_op_code', $item);
        }
        $this->assertCount(1, $items);
        $this->assertSame('Airtel DTH', $items[0]['name']);
    }

    public function test_import_hides_topup_mart_dth_from_catalog(): void
    {
        $ctx = $this->seedProviders();
        // Pretend Topup Mart DTH was visible
        $ctx['tmDth']->update(['is_active' => true]);

        $result = (new ServiceImporter())->importFromProvider($ctx['hrc']);

        $this->assertGreaterThanOrEqual(0, $result['imported']);
        $this->assertFalse($ctx['tmDth']->fresh()->is_active);
        $this->assertTrue($ctx['hrcDth']->fresh()->is_active);
    }

    public function test_dth_order_goes_to_hrc_and_stays_pending(): void
    {
        $ctx = $this->seedProviders();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);

        Http::fake([
            '*happyrechargecenter.com/RechargeApi/Recharge.aspx*' => Http::response([
                'STATUS' => 'IN PROCESS', 'TRANSACTIONID' => '4174111',
                'OPERATORID' => '', 'CLIENTID' => 'HPR-TEST', 'MESSAGE' => '',
            ], 200),
        ]);

        $order = app(OrderService::class)->placeOrder(
            user: $user,
            serviceId: $ctx['hrcDth']->id,
            accountNumber: '3001234567',
            amount: 500,
        );

        $this->assertSame('pending', $order->status);
        $this->assertTrue($order->provider->isHappyRechargeCenter());
        $this->assertSame('4174111', $order->provider_txn_id);
        $this->assertEquals(500, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_admin_failover_resends_same_order_via_topup_mart(): void
    {
        $ctx = $this->seedProviders();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        Http::fake([
            'happyrechargecenter.com/RechargeApi/Recharge.aspx*' => Http::response([
                'STATUS' => 'IN PROCESS', 'TRANSACTIONID' => '4174111',
                'OPERATORID' => '', 'CLIENTID' => '', 'MESSAGE' => '',
            ], 200),
            'happyrechargecenter.com/RechargeApi/CancelRecharge.aspx*' => Http::response('Not Found', 404),
            'happyrechargecenter.com/RechargeApi/RechargeCancel.aspx*' => Http::response('Not Found', 404),
            'topupmart.online/api/v2/recharge.php' => Http::response([
                'status' => 'success', 'transaction_id' => 'TM-99', 'message' => 'OK',
            ], 200),
        ]);

        $svc = app(OrderService::class);
        $order = $svc->placeOrder($user, $ctx['hrcDth']->id, '3001234567', 500);
        $this->assertSame('pending', $order->status);

        $updated = $svc->failoverToTopupMart($order, $admin, 'stuck pending');

        $this->assertSame($order->id, $updated->id, 'must be the same order, not a new one');
        $this->assertSame('success', $updated->status);
        $this->assertTrue($updated->provider->isTopupMart());
        $this->assertSame('TM-99', $updated->provider_txn_id);
        $this->assertSame($ctx['tmDth']->id, $updated->service_id);
        $this->assertEquals(500, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertArrayHasKey('_failover', $updated->provider_response);
    }
}
