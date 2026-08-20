@extends('layouts.auth')

@section('title', 'Sign In · Happy Pratheep Recharge')

@section('content')
<div class="auth">
  <div class="wrap" style="display:flex;justify-content:center;">
    <div class="auth__card">
      <div class="auth__brand">
        <img src="{{ asset('assets/logo-mark.png') }}" alt="">
        <h1>Welcome <em>back</em></h1>
        <p>Sign in to manage your reloads, bills &amp; orders.</p>
      </div>

      @if (session('status'))
        <div class="auth__status">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="auth__errors">
          <ul>
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}" autocomplete="on">
        @csrf

        <div class="auth__field">
          <label for="email">Email address <span class="req">*</span></label>
          <input id="email" name="email" type="email" class="auth__input"
                 value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
        </div>

        <div class="auth__field">
          <label for="password">Password <span class="req">*</span></label>
          <input id="password" name="password" type="password" class="auth__input"
                 placeholder="••••••••••" required autocomplete="current-password">
        </div>

        <div class="auth__row">
          <label class="auth__check">
            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            Remember me
          </label>
          <a href="#">Forgot password?</a>
        </div>

        <button type="submit" class="auth__submit" data-loading="Signing in…">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          Sign In
        </button>

        <div class="auth__divider">New here?</div>

        <p class="auth__switch">
          Don't have an account? <a href="{{ route('register') }}">Create one</a>
        </p>
      </form>
    </div>
  </div>
</div>
@endsection
