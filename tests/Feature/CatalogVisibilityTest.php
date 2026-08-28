<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Providers\TMobiling;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function seedBoth(): array
    {
        $cat = Category::create([
            'name' => 'Mobile Reload',
            'slug' => 'mobile',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $utility = Category::create([
            'name' => 'Utility Bills',
            'slug' => 'utility',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $tm = Provider::create([
            'name' => 'Topup Mart',
            'slug' => 'topup-mart',
            'country' => 'LK',
            'api_class' => 'topup_mart',
            'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'tm-key',
            'is_active' => true,
        ]);

        $tmobi = Provider::updateOrCreate(['slug' => 'tmobiling'], [
            'name' => 'TMobiling',
            'country' => 'LK',
            'api_class' => 'tmobiling',
            'base_url' => TMobiling::DEFAULT_BASE_URL,
            'api_key' => 'tmobi-key',
            'is_active' => true,
        ]);

        $tmDialog = Service::create([
            'provider_id' => $tm->id,
            'category_id' => $cat->id,
            'op_code' => '181',
            'name' => 'Dialog Prepaid',
            'type' => 'prepaid',
            'profit' => 0,
            'profit_type' => 'FLAT',
            'is_active' => true,
        ]);

        $tmobiDialog = Service::create([
            'provider_id' => $tmobi->id,
            'category_id' => $cat->id,
            'op_code' => '1',
            'name' => 'Dialog Prepaid',
            'type' => 'prepaid',
            'profit' => 0,
            'profit_type' => 'FLAT',
            'is_active' => true,
        ]);

        $lankaBell = Service::create([
            'provider_id' => $tmobi->id,
            'category_id' => $utility->id,
            'op_code' => '9',
            'name' => 'Lanka Bell',
            'type' => 'utility',
            'profit' => 0,
            'profit_type' => 'FLAT',
            'is_active' => true,
        ]);

        return compact('cat', 'tm', 'tmobi', 'tmDialog', 'tmobiDialog', 'lankaBell');
    }

    public function test_both_providers_on_shows_both_catalogs(): void
    {
        $ctx = $this->seedBoth();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('data-service-id="'.$ctx['tmDialog']->id.'"', $html);
        $this->assertStringContainsString('data-service-id="'.$ctx['tmobiDialog']->id.'"', $html);
        $this->assertStringContainsString('Lanka Bell', $html);
        $this->assertStringNotContainsString('TMobiling', $html);
        $this->assertStringNotContainsString('Topup Mart', $html);
    }

    public function test_turning_provider_off_hides_its_services(): void
    {
        $ctx = $this->seedBoth();
        $ctx['tmobi']->update(['is_active' => false]);
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dialog Prepaid', false)
            ->assertDontSee('Lanka Bell', false);

        $this->actingAs($user)
            ->get(route('recharge.form', $ctx['lankaBell']))
            ->assertNotFound();
    }

    public function test_turning_service_off_hides_only_that_row(): void
    {
        $ctx = $this->seedBoth();
        $ctx['lankaBell']->update(['is_active' => false]);
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'Dialog Prepaid'));
        $this->assertStringNotContainsString('Lanka Bell', $html);
    }

    public function test_admin_toggle_provider_hides_from_customers(): void
    {
        $ctx = $this->seedBoth();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.providers.toggle', $ctx['tmobi']))
            ->assertRedirect();

        $this->assertFalse($ctx['tmobi']->fresh()->is_active);

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Lanka Bell', false);
    }
}
