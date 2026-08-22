@extends('layouts.auth')

@section('title', 'Set a new password · Happy Pratheep Recharge')

@section('content')
<div class="auth">
  <div class="wrap" style="display:flex;justify-content:center;">
    <div class="auth__card">
      <div class="auth__brand">
        <img src="{{ asset('assets/logo-mark.png') }}" alt="">
        <h1>Set a <em>new password</em></h1>
        <p>Choose a password you have not used here before. At least 8 characters.</p>
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

      <form method="POST" action="{{ route('password.update') }}" autocomplete="on">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="auth__field">
          <label for="email">Email address <span class="req">*</span></label>
          <input id="email" name="email" type="email" class="auth__input"
                 value="{{ old('email', $email) }}" required autocomplete="email">
        </div>

        <div class="auth__field">
          <label for="password">New password <span class="req">*</span></label>
          <input id="password" name="password" type="password" class="auth__input"
                 placeholder="At least 8 characters" required autocomplete="new-password">
        </div>

        <div class="auth__field">
          <label for="password_confirmation">Confirm new password <span class="req">*</span></label>
          <input id="password_confirmation" name="password_confirmation" type="password" class="auth__input"
                 placeholder="Repeat password" required autocomplete="new-password">
        </div>

        <button type="submit" class="auth__submit" data-loading="Saving…">
          Save new password
        </button>

        <p class="auth__switch" style="margin-top:18px;">
          Need a new link? <a href="{{ route('password.request') }}">Ask again</a>
        </p>
      </form>
    </div>
  </div>
</div>
@endsection
