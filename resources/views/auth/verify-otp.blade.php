@extends('layouts.auth')

@section('title', 'Enter code · Happy Pratheep Recharge')

@section('content')
<div class="auth">
  <div class="wrap" style="display:flex;justify-content:center;">
    <div class="auth__card">
      <div class="auth__brand">
        <img src="{{ asset('assets/logo-mark.png') }}" alt="">
        <h1>Enter your <em>code</em></h1>
        <p>
          @if($reason === 'login_new_ip')
            New location or new IP. We sent a 6-digit code to <b>{{ $emailMasked }}</b>.
          @else
            We sent a 6-digit code to <b>{{ $emailMasked }}</b> to confirm this email.
          @endif
        </p>
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

      <form method="POST" action="{{ route('otp.verify') }}" id="otpForm" autocomplete="one-time-code">
        @csrf
        <input type="hidden" name="code" id="otpCode" value="{{ old('code') }}">

        <div class="otp-boxes" id="otpBoxes">
          @for($i = 0; $i < 6; $i++)
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                   class="otp-box" data-otp-box aria-label="Digit {{ $i + 1 }}"
                   @if($i === 0) autofocus @endif>
          @endfor
        </div>

        <button type="submit" class="auth__submit" data-loading="Checking…">
          Confirm code
        </button>
      </form>

      <form method="POST" action="{{ route('otp.resend') }}" style="margin-top:14px;">
        @csrf
        <button type="submit" class="auth__resend" id="otpResend" @if($retryIn > 0) disabled @endif>
          <span id="otpResendLabel">
            @if($retryIn > 0)
              Send again in <span id="otpWait">{{ $retryIn }}</span>s
            @else
              Send a new code
            @endif
          </span>
        </button>
      </form>

      <p class="auth__switch" style="margin-top:18px;">
        Wrong account? <a href="{{ route('login') }}">Back to sign in</a>
      </p>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  var boxes = Array.prototype.slice.call(document.querySelectorAll('[data-otp-box]'));
  var hidden = document.getElementById('otpCode');
  var form = document.getElementById('otpForm');
  if (!boxes.length || !hidden) return;

  function sync(){
    hidden.value = boxes.map(function(b){ return (b.value || '').replace(/\D/g,''); }).join('');
  }
  function fill(str){
    var digits = String(str || '').replace(/\D/g,'').slice(0, 6).split('');
    boxes.forEach(function(b, i){ b.value = digits[i] || ''; });
    sync();
  }

  boxes.forEach(function(box, idx){
    box.addEventListener('input', function(){
      box.value = box.value.replace(/\D/g,'').slice(0,1);
      if (box.value && boxes[idx + 1]) boxes[idx + 1].focus();
      sync();
      if (hidden.value.length === 6) form.requestSubmit();
    });
    box.addEventListener('keydown', function(e){
      if (e.key === 'Backspace' && !box.value && boxes[idx - 1]) {
        boxes[idx - 1].focus();
      }
    });
    box.addEventListener('paste', function(e){
      e.preventDefault();
      var t = (e.clipboardData || window.clipboardData).getData('text');
      fill(t);
      if (hidden.value.length === 6) form.requestSubmit();
    });
  });

  var wait = {{ (int) $retryIn }};
  var btn = document.getElementById('otpResend');
  var label = document.getElementById('otpResendLabel');
  if (wait > 0 && btn && label){
    var t = setInterval(function(){
      wait -= 1;
      if (wait <= 0){
        clearInterval(t);
        btn.disabled = false;
        label.textContent = 'Send a new code';
        return;
      }
      label.innerHTML = 'Send again in <span id="otpWait">' + wait + '</span>s';
    }, 1000);
  }
})();
</script>
@endpush
