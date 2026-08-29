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

/**
 * Each service kind must ask for the RIGHT identifier field:
 *  - Mobile reload / postpaid -> "Mobile Number" and NO notify field
 *  - DTH -> "Smart Card / VC Number" WITH notify field
 *  - Insurance -> "Policy Number", utility -> account number, etc.
 */
class ServiceAccountFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function makeService(string $slug, string $catName, string $op, string $name, string $type): Service
    {
        $cat = Category::firstOrCreate(['slug' => $slug], [
            'name' => $catName, 'sort_order' => 1, 'is_active' => true,
        ]);
        $provider = Provider::updateOrCreate(['slug' => 'tmobiling'], [
            'name' => 'TMobiling', 'country' => 'LK', 'api_class' => 'tmobiling',
            'base_url' => TMobiling::DEFAULT_BASE_URL, 'api_key' => 'k', 'is_active' => true,
        ]);

        return Service::create([
            'provider_id' => $provider->id, 'category_id' => $cat->id,
            'op_code' => $op, 'name' => $name, 'type' => $type,
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
    }

    public function test_mobile_reload_label_and_no_notify(): void
    {
        $svc = $this->makeService('mobile', 'Mobile Reload', '1', 'Dialog Prepaid', 'prepaid');

        $this->assertSame('Mobile Number', $svc->accountFieldLabel());
        $this->assertTrue($svc->hidesNotifyNumber());
    }

    public function test_dth_uses_smart_card_and_shows_notify(): void
    {
        $svc = $this->makeService('dth', 'DTH Recharge', '20', 'Sun Direct', 'dth');

        $this->assertSame('Smart Card / VC Number', $svc->accountFieldLabel());
        $this->assertFalse($svc->hidesNotifyNumber());
    }

    public function test_insurance_uses_policy_number(): void
    {
        $svc = $this->makeService('insurance', 'Insurance', '34', 'Janashakthi Life', 'insurance');

        $this->assertSame('Policy Number', $svc->accountFieldLabel());
        $this->assertFalse($svc->hidesNotifyNumber());
    }

    public function test_electricity_uses_specific_account_labels(): void
    {
        $ceb  = $this->makeService('utility', 'Utility Bills', '29', 'CEB Electricity', 'utility');
        $leco = $this->makeService('utility', 'Utility Bills', '30', 'LECO Electricity', 'utility');
        $water = $this->makeService('utility', 'Utility Bills', '31', 'Water (NWSDB)', 'utility');

        $this->assertSame('CEB Account Number', $ceb->accountFieldLabel());
        $this->assertSame('LECO Account Number', $leco->accountFieldLabel());
        $this->assertSame('Water Account Number', $water->accountFieldLabel());
    }

    public function test_dth_form_renders_smart_card_label_and_notify_field(): void
    {
        $svc = $this->makeService('dth', 'DTH Recharge', '22', 'Dish TV', 'dth');
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        $html = $this->actingAs($user)->get(route('recharge.form', $svc))->assertOk()->getContent();

        $this->assertStringContainsString('Smart Card / VC Number', $html);
        $this->assertStringContainsString('name="notify_number"', $html);
    }

    public function test_mobile_form_hides_notify_field(): void
    {
        $svc = $this->makeService('mobile', 'Mobile Reload', '1', 'Dialog Prepaid', 'prepaid');
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        $html = $this->actingAs($user)->get(route('recharge.form', $svc))->assertOk()->getContent();

        $this->assertStringContainsString('Mobile Number', $html);
        $this->assertStringNotContainsString('name="notify_number"', $html);
    }
}
