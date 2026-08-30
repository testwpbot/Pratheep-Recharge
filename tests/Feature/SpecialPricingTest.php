<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\SpecialPrice;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpecialPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function seedService(): array
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
            'profit' => 10, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
        return compact('cat', 'p', 'svc');
    }

    public function test_special_price_is_used_when_placing_order(): void
    {
        $ctx = $this->seedService();
        $user = User::factory()->create(['is_retailer' => true]);
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        SpecialPrice::create([
            'user_id' => $user->id, 'service_id' => $ctx['svc']->id,
            'profit' => 25, 'profit_type' => 'FLAT',
        ]);

        Http::fake(['*topupmart.online/*' => Http::response([
            'status' => 'success', 'transaction_id' => 'T1', 'message' => 'ok',
        ], 200)]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['svc']->id, '0771234567', 100);

        $this->assertEquals(25, (float) $order->profit);
    }

    public function test_default_profit_used_without_override(): void
    {
        $ctx = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake(['*topupmart.online/*' => Http::response([
            'status' => 'success', 'transaction_id' => 'T1', 'message' => 'ok',
        ], 200)]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['svc']->id, '0771234567', 100);

        $this->assertEquals(10, (float) $order->profit);
    }

    protected function seedBillService(): array
    {
        $cat = Category::create(['name' => 'Utility Bills', 'slug' => 'utility', 'sort_order' => 2, 'is_active' => true]);
        $p = Provider::create([
            'name' => 'TMobiling', 'slug' => 'tmobiling', 'country' => 'LK',
            'api_class' => 'tmobiling', 'base_url' => 'https://www.tmobiling.lk/livenew/apis/api_request',
            'api_key' => 'k', 'is_active' => true,
        ]);
        $svc = Service::create([
            'provider_id' => $p->id, 'category_id' => $cat->id,
            'op_code' => '29', 'name' => 'CEB Electricity', 'type' => 'utility',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
        return compact('cat', 'p', 'svc');
    }

    public function test_negative_special_price_charges_a_per_user_fee_on_bill(): void
    {
        $ctx = $this->seedBillService();
        $user = User::factory()->create(['is_retailer' => true]);
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        // This customer is charged a LKR 30 fee on this bill service.
        SpecialPrice::create([
            'user_id' => $user->id, 'service_id' => $ctx['svc']->id,
            'profit' => -30, 'profit_type' => 'FLAT',
        ]);

        Http::fake(['*tmobiling.lk/*' => Http::response(['status' => 'success', 'transaction_id' => 'T1'], 200)]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['svc']->id, '123456', 5000);

        // Provider still gets 5000; wallet charged 5030; no cashback.
        $this->assertEquals(5000, (float) $order->amount);
        $this->assertEquals(30, $order->feeAmount());
        $this->assertEquals(5030, $order->totalPaid());
        $this->assertEquals(0, (float) $order->profit);
        $this->assertEquals(10000 - 5030, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_admin_can_save_a_negative_special_price_as_a_fee(): void
    {
        $ctx = $this->seedBillService();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.special-pricing.update', $customer), [
                'mark_retailer' => 1,
                'rows' => [
                    $ctx['svc']->id => ['enabled' => '1', 'profit' => -15, 'profit_type' => 'FLAT'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('special_prices', [
            'user_id' => $customer->id,
            'service_id' => $ctx['svc']->id,
            'profit' => -15.00,
        ]);
        $this->assertEquals(15, $ctx['svc']->calculateFee(5000, $customer->fresh()));
    }

    public function test_negative_special_price_rejected_for_non_bill_service(): void
    {
        $ctx = $this->seedService(); // mobile prepaid — not bill-like
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.special-pricing.update', $customer), [
                'rows' => [
                    $ctx['svc']->id => ['enabled' => '1', 'profit' => -20, 'profit_type' => 'FLAT'],
                ],
            ])
            ->assertSessionHasErrors('rows');

        $this->assertDatabaseMissing('special_prices', [
            'user_id' => $customer->id,
            'service_id' => $ctx['svc']->id,
        ]);
    }

    public function test_admin_can_save_special_pricing(): void
    {
        $ctx = $this->seedService();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $retailer = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.special-pricing.update', $retailer), [
                'mark_retailer' => 1,
                'rows' => [
                    $ctx['svc']->id => ['enabled' => '1', 'profit' => 7.5, 'profit_type' => 'PCT'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('special_prices', [
            'user_id' => $retailer->id,
            'service_id' => $ctx['svc']->id,
            'profit_type' => 'PCT',
        ]);
        $this->assertTrue($retailer->fresh()->is_retailer);
        $this->assertEquals(7.5, $ctx['svc']->calculateCashback(200, $retailer->fresh()));
    }
}
