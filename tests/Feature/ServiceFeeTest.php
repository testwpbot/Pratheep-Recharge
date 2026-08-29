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

/**
 * Negative profit = a customer service fee (surcharge) charged ON TOP of the
 * bill amount for bill-like services. The provider is still sent the exact
 * bill amount; the business keeps the fee.
 */
class ServiceFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function billService(float $profit, string $type = 'FLAT'): Service
    {
        $cat = Category::create(['name' => 'Utility Bills', 'slug' => 'utility', 'sort_order' => 1, 'is_active' => true]);
        $p = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'k', 'is_active' => true,
        ]);

        return Service::create([
            'provider_id' => $p->id, 'category_id' => $cat->id,
            'op_code' => '195', 'name' => 'CEB Electricity', 'type' => 'utility',
            'profit' => $profit, 'profit_type' => $type, 'is_active' => true,
        ]);
    }

    public function test_negative_profit_charges_fee_on_top_for_bill(): void
    {
        $svc = $this->billService(-50); // LKR 50 fee
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        Http::fake(['*topupmart.online/*' => Http::response(['status' => 'success', 'transaction_id' => 'TM-1'], 200)]);

        $order = app(OrderService::class)->placeOrder($user, $svc->id, '1234567890', 5000);

        // Provider still gets 5000; wallet charged 5050; no cashback.
        $this->assertEquals(5000, (float) $order->amount);
        $this->assertEquals(50, $order->feeAmount());
        $this->assertEquals(5050, $order->totalPaid());
        $this->assertEquals(0, (float) $order->profit);
        $this->assertEquals(4950, (float) Wallet::where('user_id', $user->id)->value('balance'));

        // The debit transaction is the full total charged.
        $debit = WalletTransaction::where('transactable_id', $order->id)->where('type', 'debit')->first();
        $this->assertEquals(5050, (float) $debit->amount);
    }

    public function test_percentage_fee_is_calculated_on_amount(): void
    {
        $svc = $this->billService(-2, 'PCT'); // 2% fee
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        Http::fake(['*topupmart.online/*' => Http::response(['status' => 'success', 'transaction_id' => 'TM-2'], 200)]);

        $order = app(OrderService::class)->placeOrder($user, $svc->id, '1234567890', 5000);

        $this->assertEquals(100, $order->feeAmount()); // 2% of 5000
        $this->assertEquals(5100, $order->totalPaid());
        $this->assertEquals(4900, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_fee_is_refunded_in_full_on_failure(): void
    {
        $svc = $this->billService(-50);
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        Http::fake(['*topupmart.online/*' => Http::response(['status' => 'failed', 'message' => 'bad account'], 200)]);

        $order = app(OrderService::class)->placeOrder($user, $svc->id, '1234567890', 5000);

        $this->assertSame(Order::STATUS_REFUNDED, $order->status);
        // Full 5050 (bill + fee) returned.
        $this->assertEquals(10000, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_positive_profit_still_gives_cashback_no_fee(): void
    {
        $svc = $this->billService(5); // LKR 5 cashback
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        Http::fake(['*topupmart.online/*' => Http::response(['status' => 'success', 'transaction_id' => 'TM-3'], 200)]);

        $order = app(OrderService::class)->placeOrder($user, $svc->id, '1234567890', 5000);

        $this->assertEquals(0, $order->feeAmount());
        $this->assertFalse($order->hasFee());
        $this->assertEquals(5, (float) $order->profit);
        // 10000 - 5000 + 5 cashback = 5005
        $this->assertEquals(5005, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_mobile_service_ignores_negative_profit_fee(): void
    {
        $cat = Category::create(['name' => 'Mobile', 'slug' => 'mobile', 'sort_order' => 1, 'is_active' => true]);
        $p = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'k', 'is_active' => true,
        ]);
        $svc = Service::create([
            'provider_id' => $p->id, 'category_id' => $cat->id,
            'op_code' => '181', 'name' => 'Dialog Prepaid', 'type' => 'prepaid',
            'profit' => -50, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);

        // Mobile is not fee-eligible; fee is ignored.
        $this->assertFalse($svc->allowsFee());
        $this->assertEquals(0, $svc->calculateFee(500));
    }

    public function test_admin_cannot_set_negative_profit_on_mobile(): void
    {
        $cat = Category::create(['name' => 'Mobile', 'slug' => 'mobile', 'sort_order' => 1, 'is_active' => true]);
        $p = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'k', 'is_active' => true,
        ]);
        $svc = Service::create([
            'provider_id' => $p->id, 'category_id' => $cat->id,
            'op_code' => '181', 'name' => 'Dialog Prepaid', 'type' => 'prepaid',
            'profit' => 5, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->patch(route('admin.services.update', $svc), [
            'name' => 'Dialog Prepaid', 'op_code' => '181', 'category_id' => $cat->id,
            'type' => 'prepaid', 'profit' => -50, 'profit_type' => 'FLAT', 'is_active' => '1',
        ])->assertSessionHasErrors('profit');

        $this->assertEquals(5, (float) $svc->fresh()->profit);
    }

    public function test_admin_can_set_negative_profit_on_bill(): void
    {
        $svc = $this->billService(0);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->patch(route('admin.services.update', $svc), [
            'name' => 'CEB Electricity', 'op_code' => '195', 'category_id' => $svc->category_id,
            'type' => 'utility', 'profit' => -50, 'profit_type' => 'FLAT', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(-50, (float) $svc->fresh()->profit);
    }
}
