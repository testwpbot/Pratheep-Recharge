<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileKindFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function seedMobile(): void
    {
        $cat = Category::create([
            'name' => 'Mobile',
            'slug' => 'mobile',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $provider = Provider::create([
            'name' => 'Topup Mart',
            'slug' => 'topup-mart',
            'country' => 'LK',
            'api_class' => 'topup_mart',
            'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'k',
            'is_active' => true,
        ]);

        Service::create([
            'provider_id' => $provider->id,
            'category_id' => $cat->id,
            'op_code' => '181',
            'name' => 'Dialog Prepaid',
            'type' => 'prepaid',
            'profit' => 0,
            'profit_type' => 'FLAT',
            'is_active' => true,
        ]);

        Service::create([
            'provider_id' => $provider->id,
            'category_id' => $cat->id,
            'op_code' => '171',
            'name' => 'Dialog Postpaid',
            'type' => 'postpaid',
            'profit' => 0,
            'profit_type' => 'FLAT',
            'is_active' => true,
        ]);
    }

    public function test_quick_recharge_shows_prepaid_postpaid_filter_on_mobile(): void
    {
        $this->seedMobile();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="mobileKindTabs"', false)
            ->assertSee('>Prepaid</button>', false)
            ->assertSee('>Postpaid</button>', false)
            ->assertSee('data-pay-kind="prepaid"', false)
            ->assertSee('data-pay-kind="postpaid"', false);
    }

    public function test_plans_page_shows_prepaid_postpaid_filter_on_mobile(): void
    {
        $this->seedMobile();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.plans'))
            ->assertOk()
            ->assertSee('id="mobileKindTabs"', false)
            ->assertSee('>Prepaid</button>', false)
            ->assertSee('>Postpaid</button>', false)
            ->assertSee('data-line-prepaid="1"', false)
            ->assertSee('data-line-postpaid="1"', false);
    }
}
