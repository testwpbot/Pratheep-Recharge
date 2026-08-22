<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false)
            ->assertSee('Forgot password?', false);
    }

    public function test_forgot_password_page_is_live(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot your', false)
            ->assertSee('Send reset link', false);
    }

    public function test_unknown_email_still_shows_success(): void
    {
        Mail::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Mail::assertNothingSent();
    }

    public function test_known_email_sends_reset_mail(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'asha@example.com']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($user) {
            return $mail->hasTo($user->email) && str_contains($mail->resetUrl, '/reset-password/');
        });
    }

    public function test_user_can_set_a_new_password_with_valid_token(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email'    => 'nuwan@example.com',
            'password' => 'old-password-1',
        ]);

        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Set a', false)
            ->assertSee($user->email, false);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'new-password-9',
            'password_confirmation' => 'new-password-9',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-9', $user->fresh()->password));
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_expired_or_fake_token_is_rejected(): void
    {
        $user = User::factory()->create([
            'email'    => 'bad@example.com',
            'password' => 'password12',
        ]);

        $this->from(route('password.reset', ['token' => 'not-a-real-token', 'email' => $user->email]))
            ->post(route('password.update'), [
                'token'                 => 'not-a-real-token',
                'email'                 => $user->email,
                'password'              => 'new-password-9',
                'password_confirmation' => 'new-password-9',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password12', $user->fresh()->password));
    }
}
