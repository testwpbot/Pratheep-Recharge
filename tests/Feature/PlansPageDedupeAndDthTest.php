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
 * Covers two plans-page bugs that appeared once BOTH providers
 * (Topup Mart + TMobiling) were enabled:
 *
 *  1. Duplicate "Postpaid bill payment" button — the mobile brand groups list
 *     both providers' postpaid op codes (e.g. Dialog = 171 + 12), which rendered
 *     the CTA twice. They must collapse to ONE button.
 *  2. DTH category showed no services even though the rows exist. DTH now has
 *     dedicated brand groups that render through whichever provider is enabled.
 */
class PlansPageDedupeAndDthTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    protected function seed(): array
    {
        $mobile = Category::create(['name' => 'Mobile Reload', 'slug' => 'mobile', 'sort_order' => 1, 'is_active' => true]);
        $dth    = Category::create(['name' => 'DTH Recharge', 'slug' => 'dth', 'sort_order' => 6, 'is_active' => true]);

        $topup = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'tm-key', 'is_active' => true,
        ]);
        $tmobi = Provider::updateOrCreate(['slug' => 'tmobiling'], [
            'name' => 'TMobiling', 'country' => 'LK', 'api_class' => 'tmobiling',
            'base_url' => TMobiling::DEFAULT_BASE_URL, 'api_key' => 'tmobi-key', 'is_active' => true,
        ]);

        // Dialog postpaid on BOTH providers (op 171 Topup Mart, op 12 TMobiling).
        $tmDialogPost = Service::create([
            'provider_id' => $topup->id, 'category_id' => $mobile->id, 'op_code' => '171',
            'name' => 'Dialog Postpaid', 'type' => 'postpaid', 'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
        $tmobiDialogPost = Service::create([
            'provider_id' => $tmobi->id, 'category_id' => $mobile->id, 'op_code' => '12',
            'name' => 'Dialog Postpaid', 'type' => 'postpaid', 'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);

        // DTH: Sun Direct on TMobiling (op 20) and hidden Topup Mart twin (op 122, inactive).
        $tmobiSun = Service::create([
            'provider_id' => $tmobi->id, 'category_id' => $dth->id, 'op_code' => '20',
            'name' => 'Sun Direct', 'type' => 'dth', 'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
        $topupSun = Service::create([
            'provider_id' => $topup->id, 'category_id' => $dth->id, 'op_code' => '122',
            'name' => 'Sun Direct', 'type' => 'dth', 'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => false,
        ]);

        return compact('mobile', 'dth', 'topup', 'tmobi', 'tmDialogPost', 'tmobiDialogPost', 'tmobiSun', 'topupSun');
    }

    public function test_postpaid_bill_button_is_not_duplicated_when_both_providers_on(): void
    {
        $this->seed();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        $html = $this->actingAs($user)->get(route('dashboard.plans'))->assertOk()->getContent();

        // The Dialog card must show exactly ONE "Postpaid bill payment" CTA,
        // not one per provider.
        $this->assertSame(
            1,
            substr_count($html, 'Postpaid bill payment'),
            'Dialog postpaid bill CTA should render exactly once across both providers.'
        );
    }

    public function test_dth_services_render_from_enabled_provider(): void
    {
        $this->seed();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        $html = $this->actingAs($user)->get(route('dashboard.plans'))->assertOk()->getContent();

        // DTH tab + a Sun Direct card that routes through the active TMobiling row.
        $this->assertStringContainsString('data-cat-slug="dth"', $html);
        $this->assertStringContainsString('Sun Direct', $html);
    }

    public function test_dth_falls_back_to_other_provider_when_first_is_off(): void
    {
        $ctx = $this->seed();

        // Turn TMobiling off, activate the Topup Mart DTH twin instead.
        $ctx['tmobi']->update(['is_active' => false]);
        $ctx['topupSun']->update(['is_active' => true]);

        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        $html = $this->actingAs($user)->get(route('dashboard.plans'))->assertOk()->getContent();

        // Same one DTH card, now served by Topup Mart — still exactly one.
        $this->assertStringContainsString('Sun Direct', $html);
        $this->assertSame(
            1,
            substr_count($html, 'data-op-key="sun-direct"'),
            'Sun Direct should render as a single DTH card regardless of provider.'
        );
    }
}
