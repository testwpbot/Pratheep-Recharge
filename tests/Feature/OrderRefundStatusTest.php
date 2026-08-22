<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderRefundStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function seedService(): Service
    {
        $cat = Category::create([
            'name' => 'Mobile', 'slug' => 'mobile', 'sort_order' => 1, 'is_active' => true,
        ]);
        $p = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'k', 'is_active' => true,
        ]);

        return Service::create([
            'provider_id' => $p->id, 'category_id' => $cat->id,
            'op_code' => '181', 'name' => 'Dialog Prepaid', 'type' => 'prepaid',
            'profit' => 5, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
    }

    public function test_hard_fail_marks_order_refunded_and_puts_money_back(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake([
            '*topupmart.online/*' => Http::response([
                'status' => 'failed',
                'message' => 'Invalid mobile number',
            ], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 100);

        $this->assertSame(Order::STATUS_REFUNDED, $order->status);
        $this->assertTrue($order->isRefunded());
        $this->assertSame('Refunded', $order->statusLabel());
        $this->assertEquals(500, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertEquals(1, WalletTransaction::where('transactable_type', Order::class)
            ->where('transactable_id', $order->id)
            ->where('type', 'refund')
            ->count());
        $this->assertEquals(0, (float) $user->orders()->where('status', 'success')->sum('profit'));
    }

    public function test_pending_order_stays_pending_with_no_refund(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake([
            '*topupmart.online/*' => Http::response([
                'status' => 'pending',
                'transaction_id' => 'TM-WAIT',
                'message' => 'queued',
            ], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 100);

        $this->assertSame('pending', $order->status);
        $this->assertEquals(400, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertEquals(0, WalletTransaction::where('transactable_id', $order->id)->where('type', 'refund')->count());
    }

    public function test_mark_failed_on_pending_order_refunds_once(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake([
            '*topupmart.online/*' => Http::response([
                'status' => 'pending', 'transaction_id' => 'TM-1', 'message' => 'wait',
            ], 200),
        ]);

        $orders = app(OrderService::class);
        $order = $orders->placeOrder($user, $svc->id, '0771234567', 120);
        $this->assertSame('pending', $order->status);

        $orders->markFailed($order, 'Provider later said fail');
        $orders->markFailed($order->fresh(), 'called again');

        $order->refresh();
        $this->assertSame(Order::STATUS_REFUNDED, $order->status);
        $this->assertEquals(500, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertEquals(1, WalletTransaction::where('transactable_id', $order->id)->where('type', 'refund')->count());
    }

    public function test_old_failed_order_with_refund_is_shown_as_refunded(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake([
            '*topupmart.online/*' => Http::response([
                'status' => 'failed', 'message' => 'Insufficient balance',
            ], 200),
        ]);

        $orders = app(OrderService::class);
        $order = $orders->placeOrder($user, $svc->id, '0771234567', 80);
        $this->assertSame(Order::STATUS_REFUNDED, $order->status);

        $order->update(['status' => 'failed']);
        $orders->markFailed($order->fresh());

        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
        $this->assertEquals(500, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertEquals(1, WalletTransaction::where('transactable_id', $order->id)->where('type', 'refund')->count());
    }

    public function test_cron_fail_marks_pending_order_refunded(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 300]);

        Http::fake([
            '*topupmart.online/api/v2/recharge.php' => Http::response([
                'status' => 'pending', 'transaction_id' => 'TM-P', 'message' => 'queued',
            ], 200),
            '*topupmart.online/api/v2/status.php' => Http::response([
                'status' => 'failed', 'message' => 'Carrier rejected',
            ], 200),
        ]);

        $orders = app(OrderService::class);
        $order = $orders->placeOrder($user, $svc->id, '0771234567', 100);
        $this->assertSame('pending', $order->status);

        $this->assertSame(1, $orders->syncPending());
        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
        $this->assertEquals(300, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_customer_pages_show_refunded_not_failed(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 400]);

        Http::fake([
            '*topupmart.online/*' => Http::response([
                'status' => 'failed', 'message' => 'Invalid number',
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson(route('recharge.confirm'), [
                'service_id' => $svc->id,
                'account_number' => '0771234567',
                'amount' => 100,
            ])
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('status', 'refunded')
            ->assertJsonPath('message', 'This recharge did not go through. LKR 100.00 was put back in your wallet.');

        $order = Order::first();
        $this->assertNotNull($order);

        $this->actingAs($user)
            ->get(route('recharge.show', $order))
            ->assertOk()
            ->assertSee('Refunded', false)
            ->assertSee('was put back in your wallet', false)
            ->assertDontSee('>Failed<', false);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Refunded', false);

        $this->actingAs($user)
            ->get(route('recharge.history'))
            ->assertOk()
            ->assertSee('Refunded', false);

        $this->actingAs($user)
            ->get(route('refunds'))
            ->assertOk()
            ->assertSee($order->reference, false)
            ->assertSee('Recharge failed — refunded to wallet', false);
    }

    public function test_admin_orders_filter_and_page_show_refunded(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 400]);
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->forceFill(['admin_role' => User::ADMIN_ROLE_ADMIN])->save();

        Http::fake([
            '*topupmart.online/*' => Http::response([
                'status' => 'failed', 'message' => 'No provider money',
            ], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 100);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['status' => 'refunded']))
            ->assertOk()
            ->assertSee($order->reference, false)
            ->assertSee('Refunded', false);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['status' => 'failed']))
            ->assertOk()
            ->assertDontSee($order->reference, false);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Refunded', false)
            ->assertSee('put back in the customer wallet', false);
    }
}
