<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Happy Pratheep Recharge');
        $response->assertSee('Recharge in');
    }

    public function test_home_top_bar_uses_support_phone_and_email_from_settings(): void
    {
        Setting::set('general', 'support_phone', '+94 77 555 0000');
        Setting::set('general', 'support_email', 'hello@hp-pratheep.online');

        $this->get('/')
            ->assertOk()
            ->assertSee('+94 77 555 0000', false)
            ->assertSee('hello@hp-pratheep.online', false);
    }
}
