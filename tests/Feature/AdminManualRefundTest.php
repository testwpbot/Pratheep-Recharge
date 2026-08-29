<?php

namespace Tests\Feature;

use App\Models\Cashback;
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

/**
 * Admin manual refund from the orders page:
 *  - returns the order charge to the customer wallet
 *  - marks the order Refunded
 *  - reverses any cashback the order earned
 *  - is idempotent
 *  - requires the "provider not auto-refunded" acknowledgement
 */
class AdminManualRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function seedService(): Service
    {
        $cat = Category::create(['name' => 'Mobile', 'slug' => 'mobile', 'sort_order' => 1, 'is_active' => true]);
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

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_manual_refund_puts_money_back_and_marks_refunded(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        // Pending order (money debited, not refunded).
        Http::fake(['*topupmart.online/*' => Http::response(['status' => 'pending', 'transaction_id' => 'TM-1'], 200)]);
        $order = app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 100);
        $this->assertSame('pending', $order->status);
        $this->assertEquals(400, (float) Wallet::where('user_id', $user->id)->value('balance'));

        $result = app(OrderService::class)->manualRefund($order->fresh(), $this->admin(), 'customer complaint');

        $this->assertTrue($result['refunded']);
        $this->assertFalse($result['already']);
        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
        $this->assertEquals(500, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertTrue($order->fresh()->wasManuallyRefunded());
        $this->assertEquals(1, WalletTransaction::where('transactable_id', $order->id)->where('type', 'refund')->count());
    }

    public function test_manual_refund_of_success_reverses_cashback(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake(['*topupmart.online/*' => Http::response(['status' => 'success', 'transaction_id' => 'TM-2'], 200)]);
        $order = app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 100);

        $this->assertSame('success', $order->status);
        // 500 - 100 debit + 5 cashback = 405
        $this->assertEquals(405, (float) Wallet::where('user_id', $user->id)->value('balance'));

        $result = app(OrderService::class)->manualRefund($order->fresh(), $this->admin());

        $this->assertEquals(5.0, (float) $result['cashback_reversed']);
        // 405 + 100 refund - 5 cashback reversal = 500
        $this->assertEquals(500, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertSame('reversed', Cashback::where('order_id', $order->id)->value('status'));
    }

    public function test_manual_refund_is_idempotent(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake(['*topupmart.online/*' => Http::response(['status' => 'pending', 'transaction_id' => 'TM-3'], 200)]);
        $order = app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 100);

        app(OrderService::class)->manualRefund($order->fresh(), $this->admin());
        $second = app(OrderService::class)->manualRefund($order->fresh(), $this->admin());

        $this->assertTrue($second['already']);
        $this->assertEquals(500, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertEquals(1, WalletTransaction::where('transactable_id', $order->id)->where('type', 'refund')->count());
    }

    public function test_refund_endpoint_requires_acknowledgement(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake(['*topupmart.online/*' => Http::response(['status' => 'pending', 'transaction_id' => 'TM-4'], 200)]);
        $order = app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 100);

        // Missing acknowledgement -> validation error, no refund.
        $this->actingAs($this->admin())
            ->post(route('admin.orders.refund', $order), ['note' => 'x'])
            ->assertSessionHasErrors('acknowledged');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertEquals(400, (float) Wallet::where('user_id', $user->id)->value('balance'));

        // With acknowledgement -> refunded.
        $this->actingAs($this->admin())
            ->post(route('admin.orders.refund', $order), ['acknowledged' => '1', 'note' => 'ok'])
            ->assertRedirect();

        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
        $this->assertEquals(500, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }
}
