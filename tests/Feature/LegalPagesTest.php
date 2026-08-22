<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_support_page_is_live(): void
    {
        $this->get('/support')
            ->assertOk()
            ->assertSee('Support', false)
            ->assertSee('How to reach us', false)
            ->assertDontSee('Coming Soon', false);
    }

    public function test_privacy_terms_and_refund_pages_are_live(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy', false);
        $this->get('/terms')->assertOk()->assertSee('Terms of Service', false);
        $this->get('/refund')
            ->assertOk()
            ->assertSee('Refund Policy', false)
            ->assertSee('same amount back in your Happy Pratheep wallet', false);
    }
}
