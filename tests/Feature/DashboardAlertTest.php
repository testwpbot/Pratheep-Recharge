<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DashboardAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create(['is_admin' => true, 'email' => 'alert-admin@example.com']);
        $user->forceFill(['admin_role' => User::ADMIN_ROLE_ADMIN])->save();

        return $user;
    }

    public function test_admin_can_create_alert(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.alerts.store'), [
                'title'        => 'Avurudu offer',
                'eyebrow'      => 'Limited offer',
                'heading'      => 'Add LKR 1000 and earn extra cashback',
                'body'         => 'Top up this week and keep recharging.',
                'button_label' => 'Add money',
                'button_url'   => '/wallet',
                'theme'        => 'gold',
                'audience'     => 'all',
                'is_active'    => '1',
                'is_dismissible' => '1',
            ])
            ->assertRedirect(route('admin.alerts.index'));

        $this->assertDatabaseHas('alerts', [
            'heading' => 'Add LKR 1000 and earn extra cashback',
            'theme'   => 'gold',
            'is_active' => 1,
        ]);
    }

    public function test_logged_in_customer_sees_alert_on_dashboard_not_homepage(): void
    {
        $customer = User::factory()->create();
        Alert::create([
            'title'     => 'Notice',
            'heading'   => 'Wallet top-up bonus this week',
            'body'      => 'Add money today.',
            'theme'     => 'navy',
            'audience'  => 'all',
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Wallet top-up bonus this week', false)
            ->assertSee('hpr-alert-pop', false);

        $this->actingAs($customer)
            ->get(route('wallet'))
            ->assertOk()
            ->assertSee('Wallet top-up bonus this week', false);

        $this->actingAs($customer)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Wallet top-up bonus this week', false);
    }

    public function test_off_or_future_alert_is_hidden(): void
    {
        $customer = User::factory()->create();
        Alert::create([
            'title'     => 'Off',
            'heading'   => 'Hidden off banner',
            'theme'     => 'navy',
            'audience'  => 'all',
            'is_active' => false,
        ]);
        Alert::create([
            'title'     => 'Later',
            'heading'   => 'Hidden future banner',
            'theme'     => 'navy',
            'audience'  => 'all',
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Hidden off banner', false)
            ->assertDontSee('Hidden future banner', false);
    }

    public function test_customer_can_dismiss_alert(): void
    {
        $customer = User::factory()->create();
        $alert = Alert::create([
            'title'          => 'Notice',
            'heading'        => 'Please add money today',
            'theme'          => 'navy',
            'audience'       => 'all',
            'is_active'      => true,
            'is_dismissible' => true,
        ]);

        $this->actingAs($customer)
            ->postJson(route('dashboard.alerts.dismiss', $alert))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Please add money today', false);
    }

    public function test_retailer_only_alert_hides_from_normal_customer(): void
    {
        $customer = User::factory()->create(['is_retailer' => false]);
        $retailer = User::factory()->create(['is_retailer' => true]);
        Alert::create([
            'title'     => 'Retail',
            'heading'   => 'Special shop bonus',
            'theme'     => 'gold',
            'audience'  => 'retailers',
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertDontSee('Special shop bonus', false);

        $this->actingAs($retailer)
            ->get(route('dashboard'))
            ->assertSee('Special shop bonus', false);
    }

    public function test_blocks_javascript_button_link(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.alerts.store'), [
                'title'        => 'Bad',
                'heading'      => 'Click me',
                'button_label' => 'Go',
                'button_url'   => 'javascript:alert(1)',
                'theme'        => 'navy',
                'audience'     => 'all',
                'is_active'    => '1',
            ])
            ->assertRedirect();

        $alert = Alert::where('heading', 'Click me')->first();
        $this->assertNotNull($alert);
        $this->assertNull($alert->button_url);
    }

    public function test_guest_cannot_open_admin_alerts(): void
    {
        $this->get(route('admin.alerts.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_upload_alert_image(): void
    {
        $admin = $this->admin();
        $file = UploadedFile::fake()->image('promo.jpg', 640, 360);

        $this->actingAs($admin)
            ->post(route('admin.alerts.store'), [
                'title'     => 'Pic',
                'heading'   => 'Festive recharge deal',
                'theme'     => 'navy',
                'audience'  => 'all',
                'is_active' => '1',
                'image'     => $file,
            ])
            ->assertRedirect();

        $alert = Alert::where('heading', 'Festive recharge deal')->first();
        $this->assertNotEmpty($alert->image_path);
        $this->assertFileExists(public_path($alert->image_path));
        @unlink(public_path($alert->image_path));
    }

    public function test_message_html_is_cleaned_and_shown(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.alerts.store'), [
                'title'     => 'Rich',
                'heading'   => 'Festival offer',
                'body'      => '<p>Add <strong>LKR 1,000</strong> today.</p><script>alert(1)</script>',
                'theme'     => 'navy',
                'audience'  => 'all',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $alert = Alert::where('heading', 'Festival offer')->first();
        $this->assertNotNull($alert);
        $this->assertStringContainsString('<strong>LKR 1,000</strong>', (string) $alert->body);
        $this->assertStringNotContainsString('<script>', (string) $alert->body);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<strong>LKR 1,000</strong>', false)
            ->assertDontSee('<script>', false);
    }
}
