<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OrderService;
use App\Support\ServicePairs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DialogServiceTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function seedDialog(): array
    {
        $cat = Category::firstOrCreate(['slug' => 'mobile'], [
            'name' => 'Mobile Reload', 'icon' => 'phone-menu', 'sort_order' => 1, 'is_active' => true,
        ]);
        $provider = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'tm-test-key', 'is_active' => true,
        ]);
        $dialog = Service::create([
            'provider_id' => $provider->id, 'category_id' => $cat->id,
            'op_code' => '181', 'name' => 'Dialog Prepaid', 'type' => 'prepaid',
            'profit' => 5, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
        $api = Service::create([
            'provider_id' => $provider->id, 'category_id' => $cat->id,
            'op_code' => '921', 'name' => 'Dialog', 'type' => 'api',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);

        return compact('cat', 'provider', 'dialog', 'api');
    }

    public function test_pairs_dialog_both_ways(): void
    {
        $ctx = $this->seedDialog();
        $this->assertSame('921', ServicePairs::partnerCode('181'));
        $this->assertSame('181', ServicePairs::partnerCode('921'));
        $this->assertSame($ctx['api']->id, ServicePairs::partner($ctx['dialog'])->id);
        $this->assertSame($ctx['dialog']->id, ServicePairs::partner($ctx['api'])->id);
    }

    public function test_dialog_prepaid_is_sent_through_dialog_api_first(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);

        Http::fake([
            'topupmart.online/api/v2/recharge.php' => Http::response([
                'status' => 'pending', 'transaction_id' => 'TM-API', 'message' => 'queued',
            ], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['dialog']->id, '0771234567', 200);

        $this->assertSame('pending', $order->status);
        $this->assertSame($ctx['dialog']->id, $order->service_id);
        $this->assertSame('921', $order->sendOpCode());
        $this->assertSame('Dialog Prepaid', $order->customerServiceName());
        $this->assertEquals(800, (float) Wallet::where('user_id', $user->id)->value('balance'));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'recharge.php')
                && ($request->data()['op_code'] ?? null) === '921';
        });
    }

    public function test_pending_dialog_api_order_can_be_sent_through_prepaid(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);
        $admin = User::factory()->create(['is_admin' => true]);

        Http::fake([
            'topupmart.online/api/v2/recharge.php' => Http::sequence()
                ->push(['status' => 'pending', 'transaction_id' => 'TM-1', 'message' => 'queued'], 200)
                ->push(['status' => 'success', 'transaction_id' => 'TM-2', 'message' => 'ok'], 200),
        ]);

        $svc = app(OrderService::class);
        $order = $svc->placeOrder($user, $ctx['dialog']->id, '0771234567', 200);
        $this->assertSame('921', $order->sendOpCode());

        $updated = $svc->transferToPairedService($order, $admin, 'stuck on API');

        $this->assertSame($order->id, $updated->id);
        $this->assertSame('181', $updated->sendOpCode());
        $this->assertSame('success', $updated->status);
        $this->assertSame('TM-2', $updated->provider_txn_id);
        $this->assertSame($order->reference . '-T1', $updated->providerClientRef());
        $this->assertEquals(805, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertArrayHasKey('_transfer', $updated->provider_response);
    }

    public function test_admin_can_post_transfer_from_order_page(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->forceFill(['admin_role' => User::ADMIN_ROLE_ADMIN])->save();

        Http::fake([
            'topupmart.online/api/v2/recharge.php' => Http::sequence()
                ->push(['status' => 'pending', 'transaction_id' => 'A1'], 200)
                ->push(['status' => 'pending', 'transaction_id' => 'A2'], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['dialog']->id, '0771234567', 100);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Send via Dialog Prepaid', false)
            ->assertSee('Sending through', false);

        $this->actingAs($admin)
            ->post(route('admin.orders.transfer', $order), ['note' => 'try prepaid'])
            ->assertRedirect();

        $this->assertSame('181', $order->fresh()->sendOpCode());
        $this->assertEquals(400, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_success_order_cannot_be_transferred(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);
        $admin = User::factory()->create(['is_admin' => true]);

        Http::fake([
            'topupmart.online/api/v2/recharge.php' => Http::response([
                'status' => 'success', 'transaction_id' => 'OK1', 'message' => 'ok',
            ], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['dialog']->id, '0771234567', 100);
        $this->assertSame('success', $order->status);

        $this->expectException(\RuntimeException::class);
        app(OrderService::class)->transferToPairedService($order, $admin);
    }
}
