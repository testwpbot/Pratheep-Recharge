@extends('layouts.dashboard')
@section('title', 'My Wallet')
@section('dash_compact', '1')

@section('content')

@php
  $totalEarned    = (float) $wallet->transactions()->where('type','cashback')->sum('amount');
  $totalDeposited = (float) $wallet->transactions()->where('type','deposit')->sum('amount');
  $singleBank = $banks->count() === 1 ? $banks->first() : null;
@endphp

<div class="wallet-hero">
  <small>Wallet Balance</small>
  <b>LKR {{ number_format($wallet->balance, 2) }}</b>
  @if(!empty($walletNotice))
    <p class="wallet-hero__note">{{ $walletNotice['message'] }}</p>
  @else
    <p class="wallet-hero__note">You must keep LKR {{ number_format($minDeposit, 2) }} in your wallet. A recharge of LKR 50 needs LKR {{ number_format($minDeposit + 50, 2) }}.</p>
  @endif
  <div class="wallet-hero__grid">
    <div><span>Cashback Earned</span><i>LKR {{ number_format($totalEarned, 2) }}</i></div>
    <div><span>Total Credited</span><i>LKR {{ number_format($totalDeposited + $totalEarned, 2) }}</i></div>
    <div><span>Pending Deposits</span><i>{{ $deposits->where('status','pending')->count() }}</i></div>
  </div>
</div>

<div class="wallet-grid">
{{-- LEFT: Deposit form --}}
<div class="card wallet-grid__main">
  <div class="card__head">
    <h3>Top Up Wallet (Bank Transfer)</h3>
  </div>

  @if($banks->isEmpty())
    <div class="alert alert--error" style="margin:0;">
      Bank details aren't configured yet. Please contact admin.
    </div>
  @else
    <div class="field" style="margin-bottom:16px;">
      <label>Send money to this account <span class="req">*</span></label>
      <div class="hpr-dd hpr-dd--block" data-hpr-dd data-wallet-bank>
        <input type="hidden" id="payToBank" value="{{ $singleBank->id ?? '' }}">
        <button type="button" class="hpr-dd__btn">
          <span class="hpr-dd__label">
            @if($singleBank)
              <span class="bank-dd-preview">
                <img src="{{ $singleBank->logoUrl() }}" alt="">
                {{ $singleBank->bank_name }}
              </span>
            @else
              Pick an account
            @endif
          </span>
          <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
        <div class="hpr-dd__menu" hidden>
          @foreach ($banks as $b)
            <button type="button" class="hpr-dd__item {{ $singleBank && $singleBank->id === $b->id ? 'is-active' : '' }}"
                    data-value="{{ $b->id }}"
                    data-label="{{ $b->bank_name }}">
              <span data-dd-preview class="bank-dd-preview">
                <img src="{{ $b->logoUrl() }}" alt="">
                {{ $b->bank_name }}
              </span>
            </button>
          @endforeach
        </div>
      </div>
      <div class="hint">Pick the account you will transfer to. Details and the form open after you choose.</div>
    </div>

    @foreach ($banks as $bank)
    <div class="wallet-payto" data-bank-card="{{ $bank->id }}" @unless($singleBank && $singleBank->id === $bank->id) hidden @endunless>
      <div class="wallet-payto__head">
        <img class="wallet-payto__logo" src="{{ $bank->logoUrl() }}" alt="{{ $bank->bank_name }}">
        <div>
          <b>{{ $bank->bank_name }}</b>
          <small>Send your transfer to this account</small>
        </div>
      </div>
      <div class="wallet-payto__rows">
        <div class="wallet-payto__row">
          <span>Bank</span>
          <b>{{ $bank->bank_name }}</b>
        </div>
        <div class="wallet-payto__row">
          <span>Account name</span>
          <b>{{ $bank->account_name }}</b>
        </div>
        <div class="wallet-payto__row">
          <span>Account number</span>
          <div class="wallet-payto__value">
            <b>{{ $bank->account_no }}</b>
            <button type="button" class="copy-btn" data-copy="{{ $bank->account_no }}" aria-label="Copy account number" title="Copy">
              <svg class="copy-btn__ic" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </button>
          </div>
        </div>
        @if($bank->branch)
          <div class="wallet-payto__row">
            <span>Branch</span>
            <b>{{ $bank->branch }}</b>
          </div>
        @endif
      </div>
      @if($bank->instructions)
        <div class="wallet-payto__note">{!! nl2br(e($bank->instructions)) !!}</div>
      @endif
    </div>
    @endforeach

    <div id="depositStep" @unless($singleBank) hidden @endunless>
      @if(!empty($general['deposit_note']))
        <div class="cashback-note" style="margin:16px 0 18px;">
          <x-icon name="bolt" :size="16"/> {{ $general['deposit_note'] }}
        </div>
      @endif

      <form id="depositForm" enctype="multipart/form-data" data-no-auto-spin>
        @csrf
        <div class="form-grid">
          <div class="field">
            <label>Amount (LKR) <span class="req">*</span></label>
            <input type="number" name="amount" min="{{ $minDeposit }}" max="500000" step="0.01" required placeholder="e.g. {{ number_format($minDeposit, 0) }}">
            <div class="hint">Smallest deposit is LKR {{ number_format($minDeposit, 2) }}. Type the exact amount you sent.</div>
          </div>
          <div class="field">
            <label>Bank you sent from <span class="req">*</span></label>
            <input type="text" name="bank_name" required placeholder="e.g. Commercial Bank, Sampath, BOC…">
          </div>
          <div class="field">
            <label>Depositor name <span class="req">*</span></label>
            <input type="text" name="depositor_name" required placeholder="Name on your bank account">
          </div>
          <div class="field">
            <label>Bank reference number <small style="color:var(--muted);">(optional)</small></label>
            <input type="text" name="reference_number" placeholder="If your app shows a reference #">
          </div>
        </div>

        <div class="field" style="margin-top:16px;">
          <label>Payment slip <span class="req">*</span></label>
          <label class="file-upload" id="fileUpload">
            <x-icon name="upload" :size="28"/>
            <div style="font-weight:700; color:var(--navy-800); margin-top:6px;" id="filePromptText">Click to upload bank slip</div>
            <div style="font-size:12px; color:var(--muted); font-weight:600;" id="filePromptHint">JPG, PNG, PDF or WEBP · max 5MB</div>
            <input type="file" name="slip" id="slipInput" accept="image/jpeg,image/png,image/webp,application/pdf" required>
          </label>

          <div class="slip-preview-box" id="slipPreview">
            <div class="slip-preview-box__row">
              <b id="slipPrevName">—</b>
              <button type="button" id="slipRemove">Remove</button>
            </div>
            <div class="slip-shimmer" id="slipShimmer"></div>
            <img id="slipPrevImg" alt="Slip preview" style="display:none;">
            <div id="slipPrevPdf" style="display:none; text-align:center; padding:30px; background:#fff; border-radius:10px; font-weight:700; color:var(--navy-800);">
              <x-icon name="bill" :size="36"/><br>PDF selected
            </div>
          </div>
        </div>

        <button type="submit" class="btn-admin btn-admin--gold" style="width:100%; height:48px; margin-top:18px; font-size:15px;">
          <span class="btn-label"><x-icon name="bolt-nav" :size="15"/> Submit Deposit Request</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </form>
    </div>
  @endif
</div>

{{-- RIGHT: Deposit history + recent tx --}}
<div class="wallet-grid__side">
  <div class="card" style="margin-bottom:20px;">
    <div class="card__head">
      <h3>Recent Activity</h3>
      <div style="display:flex; gap:6px; flex-wrap:wrap;">
        <a href="{{ route('earnings') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
          <x-icon name="gift-dr" :size="12"/> Earnings
        </a>
        <a href="{{ route('refunds') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
          <x-icon name="check-circle" :size="12"/> Refunds
        </a>
      </div>
    </div>
    <div class="tx-list">
      @forelse($transactions as $t)
        @php
          $positive = $t->isCredit();
          $iconCls = $t->type === 'cashback' ? 'tx-row__ic--cb' : ($positive ? 'tx-row__ic--dep' : 'tx-row__ic--deb');
          $icon = $t->type === 'cashback' ? 'gift-dr' : ($positive ? 'wallet' : 'bolt');
          $label = $t->typeLabel();
        @endphp
        <div class="tx-row">
          <div class="tx-row__left">
            <span class="tx-row__ic {{ $iconCls }}"><x-icon name="{{ $icon }}" :size="16"/></span>
            <div>
              <b>{{ $label }}</b>
              <small>{{ $t->description }} · {{ $t->created_at->format('M d, Y H:i') }}</small>
            </div>
          </div>
          <span class="tx-row__amt {{ $positive ? 'tx-row__amt--pos' : 'tx-row__amt--neg' }}">
            {{ $positive ? '+' : '−' }} LKR {{ number_format(abs((float) $t->amount), 2) }}
          </span>
        </div>
      @empty
        <div style="text-align:center; padding:30px; color:var(--muted); font-weight:600;">No transactions yet.</div>
      @endforelse
    </div>
    @if($transactions->hasPages())
      <div style="margin-top:14px;">{{ $transactions->links() }}</div>
    @endif
  </div>

  <div class="card">
    <div class="card__head">
      <h3>My Deposit Requests</h3>
    </div>
    <div class="deposit-history">
      @forelse($deposits as $d)
        <div class="dep-row">
          <div>
            <b>LKR {{ number_format($d->amount, 2) }}</b>
            <small>{{ $d->bank_name }} · {{ $d->created_at->format('M d, Y H:i') }} · {{ $d->reference() }}</small>
          </div>
          @if($d->status === 'pending')
            <span class="pill pill--pending">Pending</span>
          @elseif($d->status === 'approved')
            <span class="pill pill--success">Approved</span>
          @else
            <span class="pill pill--failed">Rejected</span>
          @endif
        </div>
      @empty
        <div style="text-align:center; padding:30px; color:var(--muted); font-weight:600;">No deposits yet.</div>
      @endforelse
    </div>
    @if($deposits->hasPages())
      <div style="margin-top:14px;">{{ $deposits->links() }}</div>
    @endif
  </div>
</div>
</div>

{{-- Full-page processing overlay --}}
<div class="shimmer-overlay" id="submitOverlay" hidden>
  <div class="shimmer-card">
    <div class="spin"></div>
    <div class="shimmer-card__txt">
      <b>Submitting your deposit…</b>
      <small>Uploading slip and notifying admin</small>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
.wallet-hero__note{
  position:relative;
  margin:10px 0 0;
  font-size:13.5px;
  font-weight:600;
  line-height:1.45;
  color:rgba(255,255,255,.82);
  max-width:46rem;
}
.wallet-grid{
  display:grid;
  gap:20px;
  grid-template-columns:1.4fr 1fr;
  align-items:start;
}
.wallet-grid__main{min-width:0;}
.wallet-grid__side{min-width:0; display:flex; flex-direction:column; gap:20px;}
.wallet-grid__side .card{margin:0;}

.wallet-payto{
  background:linear-gradient(135deg,#fff9ec,#fff);
  border:1px solid rgba(232,163,23,.35);
  border-radius:16px;
  padding:18px;
  margin-bottom:4px;
}
.wallet-payto[hidden]{display:none !important;}
.wallet-payto__head{
  display:flex; align-items:center; gap:14px;
  padding-bottom:14px; margin-bottom:12px;
  border-bottom:1px dashed rgba(232,163,23,.35);
}
.wallet-payto__logo{
  width:72px; height:72px; object-fit:contain; flex:none;
  background:#fff; border:1px solid var(--line); border-radius:14px; padding:8px;
}
.wallet-payto__head b{display:block; font-size:17px; font-weight:800; color:var(--navy-900);}
.wallet-payto__head small{display:block; margin-top:3px; color:var(--muted); font-weight:600;}
.wallet-payto__rows{display:flex; flex-direction:column; gap:0;}
.wallet-payto__row{
  display:flex; justify-content:space-between; align-items:flex-start; gap:16px;
  padding:10px 0; border-bottom:1px dashed rgba(232,163,23,.22); font-size:14px;
}
.wallet-payto__row:last-child{border-bottom:0; padding-bottom:0;}
.wallet-payto__row span{color:var(--muted); font-weight:600; flex:none; min-width:120px;}
.wallet-payto__row b{color:var(--navy-900); font-weight:800; text-align:right; word-break:break-word;}
.wallet-payto__value{
  display:flex; align-items:center; justify-content:flex-end; gap:8px;
  min-width:0; flex-wrap:wrap;
}
.wallet-payto__value b{text-align:right;}
.copy-btn{
  display:inline-flex; align-items:center; justify-content:center;
  width:30px; height:30px; padding:0; border-radius:8px; border:0;
  background:linear-gradient(135deg,var(--gold-300),var(--gold-500));
  color:#2a1a00; cursor:pointer; flex:none; line-height:0;
  box-shadow:0 4px 10px rgba(232,163,23,.28);
  transition:transform .15s, box-shadow .15s, background .15s;
}
.copy-btn:hover{transform:translateY(-1px); box-shadow:0 6px 14px rgba(232,163,23,.38);}
.copy-btn:active{transform:translateY(0);}
.copy-btn__ic{display:block;}
.copy-btn.is-copied{
  background:#e5f7ec; color:#15733f; box-shadow:none;
}
.wallet-payto__note{
  margin-top:12px; padding-top:12px; border-top:1px dashed rgba(232,163,23,.28);
  font-size:13px; color:var(--navy-700); font-weight:600; line-height:1.6;
}
#depositStep[hidden]{display:none !important;}

@media (max-width:960px){
  .wallet-grid{grid-template-columns:1fr;}
}
@media (max-width:520px){
  .wallet-hero{padding:14px 14px; border-radius:14px; margin-bottom:12px;}
  .wallet-hero b{font-size:24px; margin-top:2px;}
  .wallet-hero__grid{grid-template-columns:1fr 1fr; gap:6px; margin-top:10px;}
  .wallet-hero__grid div{padding:8px 8px;}
  .wallet-hero__grid i{font-size:14px;}
  .wallet-hero__grid div:last-child{grid-column:1 / -1;}
  .wallet-payto{padding:12px; border-radius:14px;}
  .wallet-payto__logo{width:48px; height:48px;}
  .wallet-payto__head{padding-bottom:10px; margin-bottom:8px; gap:10px;}
  .wallet-payto__head b{font-size:15px;}
  .wallet-payto__row{padding:8px 0;}
  .slip-shimmer{height:180px;}
  .wallet-payto{padding:14px;}
  .wallet-payto__logo{width:60px; height:60px;}
  .wallet-payto__row{flex-direction:column; gap:3px; padding:10px 0;}
  .wallet-payto__row b{text-align:left;}
  .wallet-payto__value{justify-content:flex-start;}
}

.slip-preview-box{
  margin-top:14px;
  background:#f7f9fd;
  border:1.6px dashed rgba(11,42,91,.2);
  border-radius:14px;
  padding:14px;
  display:none;
}
.slip-preview-box.show{display:block;}
.slip-preview-box img{max-width:100%; max-height:300px; border-radius:10px; display:block; margin:0 auto;}
.slip-preview-box__row{display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;}
.slip-preview-box__row b{font-size:13px; color:var(--navy-800); font-weight:700; word-break:break-all;}
.slip-preview-box__row button{background:transparent; border:0; color:#b42f2f; font-weight:700; cursor:pointer; font-size:12px;}

.slip-shimmer{
  width:100%; height:220px; border-radius:10px;
  background:linear-gradient(90deg,#eef2f8 25%,#f7f9fd 50%,#eef2f8 75%);
  background-size:200% 100%;
  animation:slipShimmerAnim 1.3s infinite linear;
  display:none;
}
.slip-shimmer.show{display:block;}
@keyframes slipShimmerAnim{
  0%{background-position:200% 0;}
  100%{background-position:-200% 0;}
}

.shimmer-overlay{
  position:fixed; inset:0; z-index:9500;
  background:rgba(7,27,61,.55);
  display:grid; place-items:center;
  backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px);
}
.shimmer-card{
  background:#fff; border-radius:16px; padding:22px 28px;
  display:flex; align-items:center; gap:16px;
  box-shadow:0 20px 60px rgba(7,27,61,.35);
  min-width:280px;
}
.shimmer-card .spin{
  width:28px; height:28px; border-radius:50%;
  border:3px solid rgba(232,163,23,.25);
  border-top-color:var(--gold-500);
  animation:btnSpin .8s linear infinite;
  flex:none;
}
.shimmer-card__txt b{display:block; font-size:15px; font-weight:800; color:var(--navy-900);}
.shimmer-card__txt small{font-size:12.5px; color:var(--muted); font-weight:600;}
</style>
@endpush

@push('scripts')
<script>
(function(){
  var MIN_SPIN_MS = 2200;

  function showBank(id){
    document.querySelectorAll('[data-bank-card]').forEach(function(card){
      card.hidden = String(card.getAttribute('data-bank-card')) !== String(id);
    });
    var step = document.getElementById('depositStep');
    if (step) step.hidden = !id;
  }

  var payTo = document.getElementById('payToBank');
  if (payTo){
    payTo.addEventListener('change', function(){ showBank(payTo.value); });
    if (payTo.value) showBank(payTo.value);
  }

  var slipInput = document.getElementById('slipInput');
  var slipPreview = document.getElementById('slipPreview');
  var slipPrevImg = document.getElementById('slipPrevImg');
  var slipPrevPdf = document.getElementById('slipPrevPdf');
  var slipPrevName = document.getElementById('slipPrevName');
  var slipShimmer = document.getElementById('slipShimmer');
  var slipRemove = document.getElementById('slipRemove');
  var filePromptText = document.getElementById('filePromptText');
  var filePromptHint = document.getElementById('filePromptHint');

  function resetSlip(){
    if (!slipInput) return;
    slipInput.value = '';
    if (slipPreview) slipPreview.classList.remove('show');
    if (slipPrevImg){ slipPrevImg.src = ''; slipPrevImg.style.display='none'; }
    if (slipPrevPdf) slipPrevPdf.style.display='none';
    if (slipShimmer) slipShimmer.classList.remove('show');
    if (filePromptText) filePromptText.textContent = 'Click to upload bank slip';
    if (filePromptHint) filePromptHint.style.display = '';
  }
  if (slipRemove) slipRemove.addEventListener('click', resetSlip);

  if (slipInput){
    slipInput.addEventListener('change', function(){
      if (!slipInput.files || !slipInput.files[0]){ resetSlip(); return; }
      var f = slipInput.files[0];
      slipPrevName.textContent = f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)';
      filePromptText.textContent = f.name;
      filePromptHint.style.display = 'none';

      slipPreview.classList.add('show');
      slipPrevImg.style.display = 'none';
      slipPrevPdf.style.display = 'none';
      slipShimmer.classList.add('show');

      if (f.type === 'application/pdf' || /\.pdf$/i.test(f.name)){
        setTimeout(function(){
          slipShimmer.classList.remove('show');
          slipPrevPdf.style.display = 'block';
        }, 650);
        return;
      }

      var reader = new FileReader();
      reader.onload = function(e){
        slipPrevImg.onload = function(){
          setTimeout(function(){
            slipShimmer.classList.remove('show');
            slipPrevImg.style.display = 'block';
          }, 500);
        };
        slipPrevImg.src = e.target.result;
      };
      reader.readAsDataURL(f);
    });
  }

  document.addEventListener('click', function(e){
    var b = e.target.closest('.copy-btn');
    if (!b || !b.dataset.copy) return;
    navigator.clipboard.writeText(b.dataset.copy).then(function(){
      b.classList.add('is-copied');
      var oldTitle = b.getAttribute('title');
      b.setAttribute('title', 'Copied');
      setTimeout(function(){
        b.classList.remove('is-copied');
        b.setAttribute('title', oldTitle || 'Copy');
      }, 1600);
    });
  });

  var form = document.getElementById('depositForm');
  if (!form) return;
  var overlay = document.getElementById('submitOverlay');

  form.addEventListener('submit', function(e){
    e.preventDefault();
    var btn = form.querySelector('button[type=submit]');
    if (typeof setBtnLoading === 'function') setBtnLoading(btn, true);
    else { btn.disabled = true; btn.classList.add('is-loading'); var sp0=btn.querySelector('.btn-spinner'); if(sp0) sp0.hidden=false; }
    if (overlay) overlay.hidden = false;

    var started = performance.now();
    var fd = new FormData(form);
    fetch('{{ route('wallet.deposit') }}', {
      method:'POST', body:fd,
      headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
      credentials:'same-origin'
    })
    .then(function(r){ return r.json().then(function(d){ return {ok:r.ok, d:d, status:r.status}; }); })
    .then(function(res){
      var elapsed = performance.now() - started;
      var wait = Math.max(0, MIN_SPIN_MS - elapsed);
      setTimeout(function(){
        if (typeof setBtnLoading === 'function') setBtnLoading(btn, false);
        else { btn.disabled = false; btn.classList.remove('is-loading'); var sp1=btn.querySelector('.btn-spinner'); if(sp1) sp1.hidden=true; }
        if (overlay) overlay.hidden = true;
        if (res.ok && res.d.ok){
          if (window.toast) window.toast(res.d.message || 'Deposit submitted!', 'success');
          setTimeout(function(){ window.location.reload(); }, 600);
        } else {
          var err = 'Something went wrong';
          if (res.d){
            if (res.d.message) err = res.d.message;
            else if (res.d.errors) err = Object.values(res.d.errors).flat().join(' · ');
          }
          if (window.toast) window.toast(err, 'error');
        }
      }, wait);
    })
    .catch(function(err){
      var elapsed = performance.now() - started;
      var wait = Math.max(0, MIN_SPIN_MS - elapsed);
      setTimeout(function(){
        if (typeof setBtnLoading === 'function') setBtnLoading(btn, false);
        else { btn.disabled = false; btn.classList.remove('is-loading'); var sp2=btn.querySelector('.btn-spinner'); if(sp2) sp2.hidden=true; }
        if (overlay) overlay.hidden = true;
        if (window.toast) window.toast(err.message || 'Network error', 'error');
      }, wait);
    });
  });
})();
</script>
@endpush
