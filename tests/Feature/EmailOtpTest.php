<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationCode;
use App\Models\EmailOtp;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_sends_otp_and_does_not_login(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name'                  => 'Nuwan Perera',
            'email'                 => 'nuwan@example.com',
            'phone'                 => '0771234567',
            'password'              => 'password12',
            'password_confirmation' => 'password12',
        ])->assertRedirect(route('otp.show'));

        $this->assertGuest();
        $user = User::where('email', 'nuwan@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull(Wallet::where('user_id', $user->id)->first());
        Mail::assertSent(EmailVerificationCode::class, function (EmailVerificationCode $mail) {
            return strlen($mail->code) === 6 && $mail->purpose === EmailOtp::PURPOSE_SIGNUP;
        });
    }

    public function test_correct_signup_otp_verifies_email(): void
    {
        Mail::fake();
        $this->post('/register', [
            'name' => 'Asha', 'email' => 'asha@example.com', 'phone' => '0771111111',
            'password' => 'password12', 'password_confirmation' => 'password12',
        ]);

        $code = null;
        Mail::assertSent(EmailVerificationCode::class, function (EmailVerificationCode $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        $this->post(route('otp.verify'), ['code' => $code])
            ->assertRedirect(route('login'));

        $this->assertNotNull(User::where('email', 'asha@example.com')->value('email_verified_at'));
        $this->assertGuest();
    }

    public function test_wrong_otp_is_rejected(): void
    {
        Mail::fake();
        $this->post('/register', [
            'name' => 'Asha', 'email' => 'asha@example.com', 'phone' => '0771111111',
            'password' => 'password12', 'password_confirmation' => 'password12',
        ]);

        $this->post(route('otp.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull(User::where('email', 'asha@example.com')->value('email_verified_at'));
    }

    public function test_verified_user_same_ip_logs_in_without_otp(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email'         => 'same@example.com',
            'password'      => 'password12',
            'last_login_ip' => '127.0.0.1',
        ]);

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password12',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        Mail::assertNothingSent();
    }

    public function test_new_ip_requires_otp_before_login(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email'         => 'newip@example.com',
            'password'      => 'password12',
            'last_login_ip' => '1.2.3.4',
        ]);

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password12',
        ])->assertRedirect(route('otp.show'));

        $this->assertGuest();
        Mail::assertSent(EmailVerificationCode::class);

        $code = null;
        Mail::assertSent(EmailVerificationCode::class, function (EmailVerificationCode $mail) use (&$code) {
            $code = $mail->code;
            return $mail->purpose === EmailOtp::PURPOSE_LOGIN;
        });

        $this->post(route('otp.verify'), ['code' => $code])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('127.0.0.1', $user->fresh()->last_login_ip);
    }

    public function test_unverified_user_must_confirm_email_on_login(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create([
            'email'    => 'open@example.com',
            'password' => 'password12',
        ]);

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password12',
        ])->assertRedirect(route('otp.show'));

        $this->assertGuest();
        Mail::assertSent(EmailVerificationCode::class);
    }
}
