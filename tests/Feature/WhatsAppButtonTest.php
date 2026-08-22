<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppButtonTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $u = User::factory()->create(['is_admin' => true]);
        $u->forceFill(['admin_role' => User::ADMIN_ROLE_MAIN])->save();

        return $u;
    }

    public function test_button_is_hidden_until_turned_on(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('class="hpr-wa"', false);
    }

    public function test_admin_can_save_whatsapp_and_customers_see_the_button(): void
    {
        $this->actingAs($this->owner())
            ->post(route('admin.settings.whatsapp'), [
                'enabled' => '1',
                'phone'   => '0771234567',
                'message' => 'Hi, I need a recharge.',
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'whatsapp']));

        $this->assertSame('1', Setting::get('whatsapp', 'enabled'));
        $this->assertSame('0771234567', Setting::get('whatsapp', 'phone'));

        $href = 'https://wa.me/94771234567?text=' . rawurlencode('Hi, I need a recharge.');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="hpr-wa"', false)
            ->assertSee($href, false);

        $customer = User::factory()->create();
        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="hpr-wa"', false)
            ->assertSee($href, false);

        $this->actingAs($this->owner())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('class="hpr-wa"', false);
    }

    public function test_cannot_turn_on_without_a_number(): void
    {
        $this->actingAs($this->owner())
            ->from(route('admin.settings.index', ['tab' => 'whatsapp']))
            ->post(route('admin.settings.whatsapp'), [
                'enabled' => '1',
                'phone'   => '',
                'message' => 'Hello',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('phone');

        $this->get(route('home'))->assertDontSee('class="hpr-wa"', false);
    }

    public function test_sri_lankan_local_number_becomes_country_code(): void
    {
        $this->assertSame('94771234567', Setting::whatsappDigits('077 123 4567'));
        $this->assertSame('94771234567', Setting::whatsappDigits('+94 77 123 4567'));
        $this->assertSame('6382842714', Setting::whatsappDigits('6382842714'));
    }
}
