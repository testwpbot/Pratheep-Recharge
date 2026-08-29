<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The /csrf-token endpoint lets AJAX forms recover from an expired session/token
 * (HTTP 419) by fetching a fresh token and retrying, instead of the customer
 * seeing a raw "CSRF token mismatch" page.
 */
class CsrfTokenEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_csrf_token_endpoint_returns_a_token(): void
    {
        $this->get(route('csrf.token'))
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_csrf_token_endpoint_works_for_guests_and_authed_users(): void
    {
        // Guests can fetch a token (needed before login too).
        $this->get('/csrf-token')->assertOk();

        // Authenticated users get one bound to their session.
        $user = User::factory()->create();
        $res = $this->actingAs($user)->getJson('/csrf-token')->assertOk();
        $this->assertNotEmpty($res->json('token'));
    }
}
