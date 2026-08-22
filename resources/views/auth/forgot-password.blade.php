@extends('layouts.auth')

@section('title', 'Forgot password · Happy Pratheep Recharge')

@section('content')
<div class="auth">
  <div class="wrap" style="display:flex;justify-content:center;">
    <div class="auth__card">
      <div class="auth__brand">
        <img src="{{ asset('assets/logo-mark.png') }}" alt="">
        <h1>Forgot your <em>password</em>?</h1>
        <p>Type the email on your account. We will send a reset link if it matches.</p>
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

      <form method="POST" action="{{ route('password.email') }}" autocomplete="on">
        @csrf

        <div class="auth__field">
          <label for="email">Email address <span class="req">*</span></label>
          <input id="email" name="email" type="email" class="auth__input"
                 value="{{ old('email') }}" placeholder="you@example.com" required autofocus autocomplete="email">
        </div>

        <button type="submit" class="auth__submit" data-loading="Sending…">
          Send reset link
        </button>

        <p class="auth__switch" style="margin-top:18px;">
          Remember it? <a href="{{ route('login') }}">Back to sign in</a>
        </p>
      </form>
    </div>
  </div>
</div>
@endsection
