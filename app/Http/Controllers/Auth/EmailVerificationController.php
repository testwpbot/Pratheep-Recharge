<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __construct(protected EmailOtpService $otp) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Sign in first, then we can send a verification code.',
            ]);
        }

        return view('auth.verify-otp', [
            'emailMasked' => $this->otp->maskEmail($user->email),
            'reason'      => (string) $request->session()->get('pending_otp_reason', 'signup'),
            'retryIn'     => $this->otp->secondsUntilResend($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $purpose = (string) $request->session()->get('pending_otp_purpose', EmailOtp::PURPOSE_LOGIN);
        $result = $this->otp->verify($user, (string) $request->input('code'), $purpose);

        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['error']]);
        }

        $this->otp->markVerified($user);
        $this->otp->recordTrustedLogin($user, $request);

        $remember = (bool) $request->session()->get('pending_otp_remember', false);
        $intended = $request->session()->pull('pending_otp_intended');
        $reason = (string) $request->session()->pull('pending_otp_reason', 'signup');

        $request->session()->forget([
            'pending_otp_user_id',
            'pending_otp_purpose',
            'pending_otp_remember',
        ]);

        if ($reason === 'signup') {
            return redirect()
                ->route('login')
                ->with('status', 'Email confirmed. You can sign in now.');
        }

        Auth::login($user->fresh(), $remember);
        $request->session()->regenerate();

        if ($user->is_admin) {
            return redirect()->intended($intended ?: route('admin.dashboard'));
        }

        return redirect()->intended($intended ?: route('dashboard'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $purpose = (string) $request->session()->get('pending_otp_purpose', EmailOtp::PURPOSE_LOGIN);
        $sent = $this->otp->issue($user, $purpose, $request, force: false);

        if (! $sent['ok']) {
            return back()->withErrors(['code' => $sent['error']]);
        }

        return back()->with('status', 'A new code was sent to your email.');
    }

    protected function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('pending_otp_user_id');
        if (! $id) {
            return null;
        }

        return User::find($id);
    }
}
