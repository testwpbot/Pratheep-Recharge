<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RechargeNotifyAndConfirmTest extends TestCase
{
    use RefreshDatabase;

    protected function provider(): Provider
    {
        return Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'k', 'is_active' => true,
        ]);
    }

    protected function service(string $catSlug, string $type, string $name): Service
    {
        $cat = Category::firstOrCreate(
            ['slug' => $catSlug],
            ['name' => ucfirst($catSlug), 'sort_order' => 1, 'is_active' => true]
        );

        return Service::create([
            'provider_id' => $this->provider()->id,
            'category_id' => $cat->id,
            'op_code' => (string) random_int(100, 999),
            'name' => $name,
            'type' => $type,
            'profit' => 0,
            'profit_type' => 'FLAT',
            'is_active' => true,
        ]);
    }

    public function test_mobile_reload_form_hides_notify_number(): void
    {
        $user = User::factory()->create();
        $svc = $this->service('mobile', 'prepaid', 'Dialog Prepaid');

        $this->actingAs($user)
            ->get(route('recharge.form', $svc))
            ->assertOk()
            ->assertDontSee('Notify number', false)
            ->assertSee('Place Order', false)
            ->assertDontSee('Check this payment', false);
    }

    public function test_mobile_postpaid_form_hides_notify_number(): void
    {
        $user = User::factory()->create();
        $svc = $this->service('mobile', 'postpaid', 'Dialog Postpaid');

        $this->actingAs($user)
            ->get(route('recharge.form', $svc))
            ->assertOk()
            ->assertDontSee('Notify number', false)
            ->assertSee('Pay Bill Now', false);
    }

    public function test_utility_bill_form_keeps_notify_and_asks_to_confirm(): void
    {
        $user = User::factory()->create();
        $svc = $this->service('utility', 'utility', 'CEB Electricity');

        $this->actingAs($user)
            ->get(route('recharge.form', $svc))
            ->assertOk()
            ->assertSee('Notify number', false)
            ->assertSee('Pay Bill Now', false)
            ->assertSee('Check this payment', false)
            ->assertSee('Yes, pay now', false);
    }

    public function test_dashboard_mobile_cards_mark_notify_hidden(): void
    {
        $user = User::factory()->create();
        $this->service('mobile', 'prepaid', 'Hutch Prepaid');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-hide-notify="1"', false)
            ->assertSee('data-category="mobile"', false)
            ->assertSee('rcNotifyField', false)
            ->assertSee('Confirm this reload?', false)
            ->assertSee('Yes, reload now', false);
    }

    public function test_plans_page_asks_to_confirm_reload(): void
    {
        $user = User::factory()->create();
        $this->service('mobile', 'prepaid', 'Dialog Prepaid');

        $this->actingAs($user)
            ->get(route('dashboard.plans'))
            ->assertOk()
            ->assertSee('Confirm this reload?', false)
            ->assertSee('Yes, reload now', false)
            ->assertSee('showOrderConfirm', false);
    }
}
