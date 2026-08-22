@extends('layouts.auth')

@section('title', 'Create Account · Happy Pratheep Recharge')

@section('content')
<div class="auth">
  <div class="wrap" style="display:flex;justify-content:center;">
    <div class="auth__card" style="max-width:520px;">
      <div class="auth__brand">
        <img src="{{ asset('assets/logo-mark.png') }}" alt="">
        <h1>Create your <em>account</em></h1>
        <p>Join 25,000+ Sri Lankans topping up &amp; paying bills the fast way.</p>
      </div>

      @if ($errors->any())
        <div class="auth__errors">
          <ul>
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('register') }}" autocomplete="on">
        @csrf

        <div class="auth__field">
          <label for="name">Full name <span class="req">*</span></label>
          <input id="name" name="name" type="text" class="auth__input"
                 value="{{ old('name') }}" placeholder="e.g. Nuwan Perera" required autofocus>
        </div>

        <div class="auth__field">
          <label for="email">Email address <span class="req">*</span></label>
          <input id="email" name="email" type="email" class="auth__input"
                 value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email">
        </div>

        <div class="auth__field">
          <label for="phone">Phone number <span class="req">*</span></label>
          <input id="phone" name="phone" type="tel" class="auth__input"
                 value="{{ old('phone') }}" placeholder="+94 77 123 4567" required autocomplete="tel">
          <small style="color:var(--muted);font-size:12px;font-weight:500;margin-top:6px;display:block;">
            We require a phone number so our team can reach you about orders.
          </small>
        </div>

        <div class="auth__field">
          <label for="password">Password <span class="req">*</span></label>
          <input id="password" name="password" type="password" class="auth__input"
                 placeholder="At least 8 characters" required autocomplete="new-password">
        </div>

        <div class="auth__field">
          <label for="password_confirmation">Confirm password <span class="req">*</span></label>
          <input id="password_confirmation" name="password_confirmation" type="password" class="auth__input"
                 placeholder="Repeat password" required autocomplete="new-password">
        </div>

        <p class="auth__hint" style="margin:4px 0 0;color:var(--muted);font-size:13px;font-weight:600;line-height:1.5;">
          After you create your account, add at least LKR {{ number_format(\App\Support\WalletLimits::minDeposit(), 2) }} to your wallet to start recharging.
        </p>

        <button type="submit" class="auth__submit" style="margin-top:10px;" data-loading="Creating account…">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 11h-6M19 8v6"/>
          </svg>
          Create Account
        </button>

        <div class="auth__divider">Already a member?</div>

        <p class="auth__switch">
          Have an account? <a href="{{ route('login') }}">Sign in</a>
        </p>
      </form>
    </div>
  </div>
</div>
@endsection
