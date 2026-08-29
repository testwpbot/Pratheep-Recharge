@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')
@section('title', "{$service->name} — Recharge")
@section('dash_compact', '1')

@push('styles')
<style>.amount-chips button{font-family:inherit;}</style>
@endpush

@section('content')

@auth
  <a href="{{ route('dashboard') }}" class="btn-admin btn-admin--ghost btn-admin--sm" style="margin-bottom:18px; display:inline-flex;">← Back to Dashboard</a>
@else
  <section class="sec sec--light"><div class="wrap" style="max-width:640px;">
  <a href="{{ route('recharge.index') }}" class="btn-admin btn-admin--ghost btn-admin--sm" style="margin-bottom:18px; display:inline-flex;">← All services</a>
@endauth

<div class="card" @if(!auth()->check()) style="margin-bottom:40px;" @endif>
  <div style="display:flex; align-items:center; gap:18px; padding-bottom:20px; border-bottom:1px solid var(--line); margin-bottom:20px;">
    <img src="{{ $service->logo ? asset($service->logo) : asset('assets/logo-mark.png') }}"
         alt="{{ $service->name }}" style="width:60px; height:60px; object-fit:contain;"
         onerror="this.src='{{ asset('assets/logo-mark.png') }}'">
    <div>
      <h2 style="margin:0; font-size:22px; font-weight:800; color:var(--navy-900); letter-spacing:-.02em;">{{ $service->name }}</h2>
      <p style="margin:4px 0 0; color:var(--muted); font-size:14px;">{{ ucfirst($service->type) }} · Quick recharge</p>
    </div>
  </div>

  @auth
    <form method="POST" action="{{ route('recharge.confirm') }}">
      @csrf
      <input type="hidden" name="service_id" value="{{ $service->id }}">

      <div class="form-grid">
        <div class="field" style="grid-column:1/-1;">
          <label>{{ $service->accountFieldLabel() }} <span class="req">*</span></label>
          <input type="tel" name="account_number" required pattern="[0-9A-Za-z+()\s-]{4,30}"
                 value="{{ old('account_number', $service->hidesNotifyNumber() ? auth()->user()->phone : '') }}"
                 placeholder="{{ $service->accountFieldPlaceholder() }}">
          <div class="hint">{{ $service->accountFieldHint() }}</div>
        </div>

        @unless($service->hidesNotifyNumber())
        <div class="field">
          <label>Notify number (optional)</label>
          <input type="tel" name="notify_number" value="{{ old('notify_number') }}" placeholder="SMS confirmation number">
        </div>
        @endunless

        @php $isDth = $service->isDth(); $fxRate = $service->fxRate(); @endphp
        <div class="field">
          <label>{{ $isDth ? 'Pack Amount (INR)' : 'Amount (LKR)' }} <span class="req">*</span></label>
          <input type="number" step="0.01" min="10" max="100000" name="amount" id="amount"
                 data-fx-rate="{{ $fxRate }}"
                 value="{{ old('amount') }}" placeholder="{{ $isDth ? 'e.g. 500 (INR)' : 'e.g. 100' }}" required>
          <div class="hint">
            @if($isDth)
              Enter the DTH pack value in Indian Rupees (INR). Your LKR wallet is charged INR × {{ $fxRate }}.
            @else
              Pick a plan below or enter a custom amount. You pay exactly this amount.
            @endif
          </div>
          @if($isDth)
          <div class="hint" id="fxNote" style="display:none; font-weight:600; color:var(--gold, #c9a227);"></div>
          @endif
        </div>
      </div>

      @if ($service->plans->isNotEmpty())
        <div class="plan-picker" id="planPicker">
          <p class="plan-picker__label">
            <x-icon name="gift" :size="14"/> Available plans for {{ $service->name }}
          </p>
          <div class="plan-picker__grid">
            @foreach ($service->plans as $p)
              <button type="button" class="pick" data-value="{{ $p->amount }}" data-name="{{ $p->name }}">
                <b>LKR {{ number_format($p->amount, 0) }}</b>
                <span>{{ $p->name }}</span>
                @if ($p->validity)<em>{{ $p->validity }}</em>@endif
                @if ($p->cashback() > 0)<i>+LKR {{ number_format($p->cashback(), 2) }}</i>@endif
              </button>
            @endforeach
          </div>
        </div>
      @endif

      {{-- Fallback generic quick-amounts when no plans are configured --}}
      @if ($service->plans->isEmpty())
        <div class="amount-chips" data-target="amount">
          @foreach ([50,100,200,500,1000,2000,5000] as $v)
            <button type="button" data-value="{{ $v }}">LKR {{ number_format($v) }}</button>
          @endforeach
        </div>
      @endif

      <div class="cashback-note" id="cashbackNote" style="display:none;">
        <x-icon name="bolt" :size="18"/>
        <span id="cashbackText"></span>
      </div>

      <div style="margin-top:22px; display:flex; gap:10px; flex-wrap:wrap;">
        <button type="submit" class="btn-admin btn-admin--gold" data-loading="Processing…" id="rechargeSubmit">
          <x-icon name="{{ $service->isBillLike() ? 'bill' : 'bolt-nav' }}" :size="18"/>
          {{ $service->isBillLike() ? 'Pay Bill Now' : 'Place Order' }}
        </button>
        <a href="{{ route('dashboard') }}" class="btn-admin btn-admin--ghost">Cancel</a>
      </div>
    </form>

    @if($service->isBillLike())
    <div class="rc-modal" id="billConfirm" hidden>
      <div class="rc-modal__backdrop" data-bill-back></div>
      <div class="rc-modal__dialog" role="dialog" aria-modal="true" style="max-width:400px; text-align:center;">
        <h3 style="margin:0 0 8px; font-size:18px; font-weight:800; color:var(--navy-900);">Check this payment</h3>
        <p id="billConfirmText" style="margin:0 0 18px; font-size:14px; font-weight:600; color:var(--navy-800); line-height:1.55;"></p>
        <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
          <button type="button" class="btn-admin btn-admin--ghost" data-bill-back>Go back</button>
          <button type="button" class="btn-admin btn-admin--gold" id="billConfirmYes">Yes, pay now</button>
        </div>
      </div>
    </div>
    @endif
  @else
    <p style="color:var(--muted); line-height:1.7;">
      You need to sign in before you can place a recharge. It only takes a moment and
      your cashback balance will start accumulating from your very first order.
    </p>
    <div style="margin-top:16px; display:flex; gap:10px;">
      <a href="{{ route('login') }}" class="btn-admin btn-admin--primary">Sign In</a>
      <a href="{{ route('register') }}" class="btn-admin btn-admin--gold">Create Account</a>
    </div>
  @endauth
</div>

@if(!auth()->check())
  </div></section>
@endif

@push('styles')
<style>
.plan-picker{margin-top:18px;}
.plan-picker__label{
  display:flex; align-items:center; gap:8px;
  font-size:13px; font-weight:800; color:var(--navy-800);
  text-transform:uppercase; letter-spacing:.08em;
  margin:0 0 10px;
}
.plan-picker__grid{
  display:grid; gap:8px;
  grid-template-columns:repeat(auto-fill, minmax(150px, 1fr));
}
.plan-picker__grid .pick{
  display:flex; flex-direction:column; gap:2px; align-items:flex-start;
  padding:11px 12px; border-radius:11px; cursor:pointer;
  background:#f7f9fd; border:1.5px solid transparent;
  font:inherit; text-align:left; color:var(--navy-800);
  transition:border-color .2s, background .2s, transform .15s, box-shadow .2s;
}
.plan-picker__grid .pick:hover{
  border-color:rgba(11,42,91,.18); background:#fff;
}
.plan-picker__grid .pick.active{
  border-color:var(--gold-500);
  background:linear-gradient(135deg,rgba(232,163,23,.12),rgba(255,209,102,.2));
  box-shadow:0 4px 12px rgba(232,163,23,.25);
}
.plan-picker__grid .pick b{font-size:14px; font-weight:800; color:var(--navy-900);}
.plan-picker__grid .pick span{font-size:12px; font-weight:600; color:var(--muted); line-height:1.2;}
.plan-picker__grid .pick em{font-size:11px; font-style:normal; font-weight:700; color:var(--navy-700);}
.plan-picker__grid .pick i{font-size:10.5px; font-style:normal; font-weight:800; color:var(--gold-600);}
@media (max-width:540px){
  .plan-picker__grid{grid-template-columns:repeat(2,1fr);}
}
</style>
@endpush

@auth
<script>
(function(){
  const picker = document.getElementById('planPicker');
  const input = document.getElementById('amount');
  const profit = {{ (float) $service->profit }};
  const profitType = '{{ $service->profit_type }}';
  const note = document.getElementById('cashbackNote');
  const txt = document.getElementById('cashbackText');

  // Plan chip click (real plans from DB)
  if (picker){
    picker.querySelectorAll('.pick').forEach(btn => {
      btn.addEventListener('click', () => {
        input.value = btn.dataset.value;
        input.dispatchEvent(new Event('input'));
        picker.querySelectorAll('.pick').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });
  }

  // Legacy amount-chips (used when no plans configured)
  document.querySelectorAll('.amount-chips').forEach(group => {
    group.querySelectorAll('button').forEach(btn => {
      btn.addEventListener('click', () => {
        input.value = btn.dataset.value;
        input.dispatchEvent(new Event('input'));
        group.querySelectorAll('button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });
  });

  input.addEventListener('input', updateCashback);
  updateCashback();

  // Preselect amount from plan chip link (sessionStorage from /plans page)
  try{
    const pre = sessionStorage.getItem('hpr_preselect_amount');
    if (pre && !input.value){
      input.value = pre;
      sessionStorage.removeItem('hpr_preselect_amount');
      sessionStorage.removeItem('hpr_preselect_plan');
      updateCashback();
    }
  }catch(e){}

  const allowsFee = {{ $service->allowsFee() ? 'true' : 'false' }};
  const fxRate = parseFloat(input.dataset.fxRate || '1') || 1;
  const fxNote = document.getElementById('fxNote');

  function feeFor(amt){
    if (!allowsFee || profit >= 0) return 0;
    const f = Math.abs(profit);
    return profitType === 'PCT' ? (amt * f / 100) : f;
  }

  function updateCashback(){
    const amt = parseFloat(input.value || '0');
    if (picker) picker.querySelectorAll('.pick').forEach(b => {
      b.classList.toggle('active', Math.abs(parseFloat(b.dataset.value) - amt) < 0.01);
    });

    // DTH: show live INR -> LKR wallet conversion.
    if (fxRate > 1 && fxNote){
      if (amt > 0){
        const lkr = Math.round(amt * fxRate * 100) / 100;
        fxNote.textContent = 'INR ' + amt.toFixed(2) + ' × ' + fxRate
          + ' = LKR ' + lkr.toLocaleString('en-LK', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' from your wallet.';
        fxNote.style.display = '';
      } else {
        fxNote.style.display = 'none';
      }
    }

    if (!amt){ note.style.display='none'; return; }

    // Negative profit on a bill service = a customer service fee shown up front.
    const fee = feeFor(amt);
    if (fee > 0){
      txt.textContent = `A service fee of LKR ${fee.toFixed(2)} applies. Total to pay: LKR ${(amt+fee).toFixed(2)} (bill LKR ${amt.toFixed(2)} + fee LKR ${fee.toFixed(2)}).`;
      note.style.display='flex';
      return;
    }

    if (profit <= 0){ note.style.display='none'; return; }
    const cb = profitType === 'PCT' ? (amt*profit/100) : profit;
    txt.textContent = `You'll earn LKR ${cb.toFixed(2)} cashback on a successful order.`;
    note.style.display='flex';
  }

  var form = document.querySelector('form[action="{{ route('recharge.confirm') }}"]');

  // Refresh the CSRF token right before the real POST, then submit. This keeps
  // orders working even after the session has been idle long enough for the
  // page's token to go stale (avoids the raw "CSRF token mismatch" page).
  function submitWithFreshToken(){
    fetch('{{ route('csrf.token') }}', {headers:{'Accept':'application/json'}, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(j){
        if (j && j.token){
          var f = form.querySelector('input[name=_token]');
          if (f) f.value = j.token;
        }
      })
      .catch(function(){ /* fall back to existing token */ })
      .finally(function(){ HTMLFormElement.prototype.submit.call(form); });
  }

  var confirmBox = document.getElementById('billConfirm');
  if (form){
    var acc = form.querySelector('[name=account_number]');
    var confirmText = document.getElementById('billConfirmText');
    var allowSubmit = false;
    form.addEventListener('submit', function(e){
      if (allowSubmit) return;
      e.preventDefault();
      if (!form.reportValidity()) return;
      // Bill-like services show a confirmation step; others submit directly.
      if (confirmBox){
        var amt = parseFloat(input.value || '0');
        var num = (acc && acc.value) ? acc.value.trim() : '';
        var fee = feeFor(amt);
        if (fee > 0){
          confirmText.innerHTML = 'Pay for <b>{{ addslashes($service->name) }}</b> to <b>' + num + '</b>:<br>'
            + 'Bill amount: LKR ' + amt.toFixed(2) + '<br>'
            + 'Service fee: LKR ' + fee.toFixed(2) + '<br>'
            + '<b>Total from wallet: LKR ' + (amt+fee).toFixed(2) + '</b>';
        } else {
          confirmText.textContent = 'Pay LKR ' + amt.toFixed(2) + ' to ' + num + ' for {{ addslashes($service->name) }} from your wallet?';
        }
        confirmBox.hidden = false;
      } else {
        allowSubmit = true;
        submitWithFreshToken();
      }
    });
    if (confirmBox){
      confirmBox.querySelectorAll('[data-bill-back]').forEach(function(btn){
        btn.addEventListener('click', function(){ confirmBox.hidden = true; });
      });
      var yes = document.getElementById('billConfirmYes');
      if (yes){
        yes.addEventListener('click', function(){
          allowSubmit = true;
          confirmBox.hidden = true;
          submitWithFreshToken();
        });
      }
    }
  }
})();
</script>
@endauth

@endsection
