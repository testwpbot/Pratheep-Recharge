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

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected EmailOtpService $otp) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::validate($credentials)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'The provided credentials do not match our records.']);
        }

        $user = User::where('email', strtolower($credentials['email']))->first();
        if (! $user) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records.']);
        }

        if ($this->otp->needsChallenge($user, $request)) {
            $purpose = $user->email_verified_at ? EmailOtp::PURPOSE_LOGIN : EmailOtp::PURPOSE_SIGNUP;
            $sent = $this->otp->issue($user, $purpose, $request, force: true);

            $request->session()->put([
                'pending_otp_user_id'  => $user->id,
                'pending_otp_purpose'  => $purpose,
                'pending_otp_reason'   => $this->otp->challengeReason($user, $request),
                'pending_otp_remember' => $remember,
                'pending_otp_intended' => $request->session()->get('url.intended'),
            ]);

            if (! $sent['ok'] && $sent['error']) {
                return redirect()
                    ->route('otp.show')
                    ->withErrors(['code' => $sent['error']]);
            }

            return redirect()
                ->route('otp.show')
                ->with('status', 'We sent a 6-digit code to your email.');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->otp->recordTrustedLogin($user, $request);

        if ($user->is_admin) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
