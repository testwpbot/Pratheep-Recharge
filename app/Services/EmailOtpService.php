<?php

namespace App\Services;

use App\Mail\EmailVerificationCode;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailOtpService
{
    public function needsChallenge(User $user, Request $request): bool
    {
        if (! $user->email_verified_at) {
            return true;
        }

        $known = trim((string) $user->last_login_ip);
        if ($known === '') {
            // First login after this feature — record the IP, do not lock existing customers.
            return false;
        }

        return $known !== (string) $request->ip();
    }

    public function challengeReason(User $user, Request $request): string
    {
        if (! $user->email_verified_at) {
            return 'signup';
        }

        return 'login_new_ip';
    }

    /**
     * Create a fresh 6-digit code and email it. Older unused codes for this user stop working.
     *
     * @return array{ok:bool,otp:?EmailOtp,retry_in:int,error:?string}
     */
    public function issue(User $user, string $purpose, Request $request, bool $force = false): array
    {
        $latest = EmailOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $force && $latest) {
            $wait = $this->secondsUntilResend($user);
            if ($wait > 0) {
                return [
                    'ok'       => false,
                    'otp'      => $latest,
                    'retry_in' => $wait,
                    'error'    => 'Please wait ' . $wait . ' seconds before asking for a new code.',
                ];
            }
        }

        EmailOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = EmailOtp::create([
            'user_id'    => $user->id,
            'email'      => $user->email,
            'code_hash'  => $this->hashCode($code, $user->id),
            'purpose'    => $purpose,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(EmailOtp::TTL_MINUTES),
        ]);

        try {
            Mail::to($user->email)->send(new EmailVerificationCode(
                $user,
                $code,
                $purpose,
                $request->ip(),
            ));
        } catch (\Throwable $e) {
            Log::warning('OTP email failed: ' . $e->getMessage());

            return [
                'ok'       => false,
                'otp'      => $otp,
                'retry_in' => EmailOtp::RESEND_SECONDS,
                'error'    => 'We could not send the email. Check SMTP settings and try again.',
            ];
        }

        return [
            'ok'       => true,
            'otp'      => $otp,
            'retry_in' => EmailOtp::RESEND_SECONDS,
            'error'    => null,
        ];
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    public function verify(User $user, string $code, string $purpose): array
    {
        $code = preg_replace('/\D+/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            return ['ok' => false, 'error' => 'Enter the 6-digit code from your email.'];
        }

        $otp = EmailOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return ['ok' => false, 'error' => 'No active code. Please request a new one.'];
        }

        if ($otp->isLocked()) {
            return ['ok' => false, 'error' => 'Too many wrong tries. Request a new code.'];
        }

        if ($otp->isExpired()) {
            $otp->forceFill(['consumed_at' => now()])->save();

            return ['ok' => false, 'error' => 'That code has expired. Request a new one.'];
        }

        $otp->increment('attempts');

        if (! hash_equals($otp->code_hash, $this->hashCode($code, $user->id))) {
            $left = max(0, EmailOtp::MAX_ATTEMPTS - (int) $otp->fresh()->attempts);

            return [
                'ok'    => false,
                'error' => $left > 0
                    ? 'Wrong code. ' . $left . ' ' . ($left === 1 ? 'try' : 'tries') . ' left.'
                    : 'Too many wrong tries. Request a new code.',
            ];
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return ['ok' => true, 'error' => null];
    }

    public function recordTrustedLogin(User $user, Request $request): void
    {
        $user->forceFill([
            'last_login_ip'         => $request->ip(),
            'last_login_at'         => now(),
            'last_login_user_agent' => substr((string) $request->userAgent(), 0, 512),
        ])->save();
    }

    public function markVerified(User $user): void
    {
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }

    public function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return $email;
        }
        $name = $parts[0];
        $keep = min(2, max(1, strlen($name) - 1));

        return substr($name, 0, $keep) . str_repeat('*', max(1, strlen($name) - $keep)) . '@' . $parts[1];
    }

    public function secondsUntilResend(User $user): int
    {
        $latest = EmailOtp::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $latest) {
            return 0;
        }

        $elapsed = now()->getTimestamp() - $latest->created_at->getTimestamp();

        return max(0, EmailOtp::RESEND_SECONDS - $elapsed);
    }

    protected function hashCode(string $code, int $userId): string
    {
        return hash_hmac('sha256', $userId . '|' . $code, (string) config('app.key'));
    }
}
