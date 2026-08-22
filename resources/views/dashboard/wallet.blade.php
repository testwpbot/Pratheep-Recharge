@extends('layouts.dashboard')
@section('title', 'My Wallet')

@section('content')

@php
  $totalEarned    = (float) $wallet->transactions()->where('type','cashback')->sum('amount');
  $totalDeposited = (float) $wallet->transactions()->where('type','deposit')->sum('amount');
@endphp

<div class="wallet-hero">
  <small>Wallet Balance</small>
  <b>LKR {{ number_format($wallet->balance, 2) }}</b>
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
    @foreach ($banks as $bank)
    <div class="bank-box">
      <h5>
        <span style="display:inline-flex; align-items:center; gap:8px;">
          <img src="{{ $bank->logoUrl() }}" alt="" style="height:22px; width:auto; object-fit:contain;">
          Send payment to {{ $bank->bank_name }}
        </span>
      </h5>
      <div class="bank-row"><span>Bank</span><b>{{ $bank->bank_name }}</b></div>
      <div class="bank-row"><span>Account Name</span><b>{{ $bank->account_name }}</b></div>
      <div class="bank-row"><span>Account No</span><b>{{ $bank->account_no }} <button type="button" class="copy-btn" data-copy="{{ $bank->account_no }}">Copy</button></b></div>
      @if($bank->branch)
        <div class="bank-row"><span>Branch</span><b>{{ $bank->branch }}</b></div>
      @endif
      @if($bank->instructions)
        <div class="instructions">{!! nl2br(e($bank->instructions)) !!}</div>
      @endif
    </div>
    @endforeach

    @if(!empty($general['deposit_note']))
      <div class="cashback-note" style="margin-bottom:18px;">
        <x-icon name="bolt" :size="16"/> {{ $general['deposit_note'] }}
      </div>
    @endif

    <form id="depositForm" enctype="multipart/form-data" data-no-auto-spin>
      @csrf
      <div class="form-grid">
        <div class="field">
          <label>Amount (LKR) <span class="req">*</span></label>
          <input type="number" name="amount" min="100" max="500000" step="0.01" required placeholder="e.g. 1000">
          <div class="hint">Minimum LKR 100. The exact amount you sent.</div>
        </div>
        <div class="field">
          <label>Bank You Sent From <span class="req">*</span></label>
          <input type="text" name="bank_name" required placeholder="e.g. Commercial Bank, Sampath, BOC…">
        </div>
        <div class="field">
          <label>Depositor Name <span class="req">*</span></label>
          <input type="text" name="depositor_name" required placeholder="Name on your bank account">
        </div>
        <div class="field">
          <label>Bank Reference Number <small style="color:var(--muted);">(optional)</small></label>
          <input type="text" name="reference_number" placeholder="If your app shows a reference #">
        </div>
      </div>

      <div class="field" style="margin-top:16px;">
        <label>Payment Slip <span class="req">*</span></label>
        <label class="file-upload" id="fileUpload">
          <x-icon name="upload" :size="28"/>
          <div style="font-weight:700; color:var(--navy-800); margin-top:6px;" id="filePromptText">Click to upload bank slip</div>
          <div style="font-size:12px; color:var(--muted); font-weight:600;" id="filePromptHint">JPG, PNG, PDF or WEBP · max 5MB</div>
          <input type="file" name="slip" id="slipInput" accept="image/jpeg,image/png,image/webp,application/pdf" required>
        </label>

        {{-- Live slip preview with shimmer --}}
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
          $iconCls = $t->type === 'deposit' ? 'tx-row__ic--dep' : ($t->type === 'cashback' ? 'tx-row__ic--cb' : 'tx-row__ic--deb');
          $icon = $t->type === 'cashback' ? 'gift-dr' : ($t->type === 'deposit' ? 'wallet' : 'bolt');
          $label = match($t->type){
            'debit'      => 'Recharge / Order',
            'deposit'    => 'Deposit',
            'cashback'   => 'Cashback',
            'refund'     => 'Refund',
            'adjustment' => 'Adjustment',
            default      => ucfirst($t->type),
          };
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
/* ---------- Wallet two-column layout (collapses on mobile) ---------- */
.wallet-grid{
  display:grid;
  gap:20px;
  grid-template-columns:1.4fr 1fr;
  align-items:start;
}
.wallet-grid__main{min-width:0;}
.wallet-grid__side{min-width:0; display:flex; flex-direction:column; gap:20px;}
.wallet-grid__side .card{margin:0;}

@media (max-width:960px){
  .wallet-grid{grid-template-columns:1fr;}
}
@media (max-width:520px){
  .wallet-hero{padding:22px 20px;}
  .wallet-hero b{font-size:28px;}
  .wallet-hero__grid{grid-template-columns:1fr 1fr; gap:10px;}
  .wallet-hero__grid div{padding:10px;}
  .wallet-hero__grid i{font-size:16px;}
  .slip-shimmer{height:180px;}
  .bank-box{padding:14px;}
  .bank-row{flex-direction:column; gap:2px; padding:10px 0;}
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

/* Full-page processing overlay while the deposit is being uploaded */
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

  // ---- File upload → shimmer → preview ----
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
    slipInput.value = '';
    slipPreview.classList.remove('show');
    slipPrevImg.src = ''; slipPrevImg.style.display='none';
    slipPrevPdf.style.display='none';
    slipShimmer.classList.remove('show');
    filePromptText.textContent = 'Click to upload bank slip';
    filePromptHint.style.display = '';
  }
  if (slipRemove) slipRemove.addEventListener('click', resetSlip);

  if (slipInput){
    slipInput.addEventListener('change', function(){
      if (!slipInput.files || !slipInput.files[0]){ resetSlip(); return; }
      var f = slipInput.files[0];
      slipPrevName.textContent = '📎 ' + f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)';
      filePromptText.textContent = f.name;
      filePromptHint.style.display = 'none';

      slipPreview.classList.add('show');
      slipPrevImg.style.display = 'none';
      slipPrevPdf.style.display = 'none';
      slipShimmer.classList.add('show');

      if (f.type === 'application/pdf' || /\.pdf$/i.test(f.name)){
        // Brief shimmer then show PDF badge
        setTimeout(function(){
          slipShimmer.classList.remove('show');
          slipPrevPdf.style.display = 'block';
        }, 650);
        return;
      }

      var reader = new FileReader();
      reader.onload = function(e){
        slipPrevImg.onload = function(){
          // Short shimmer for nice feel
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

  // ---- Copy button ----
  document.querySelectorAll('.copy-btn').forEach(function(b){
    b.addEventListener('click', function(){
      navigator.clipboard.writeText(b.dataset.copy).then(function(){
        var orig = b.textContent; b.textContent = 'Copied!';
        setTimeout(function(){ b.textContent = orig; }, 1500);
      });
    });
  });

  // ---- Submit with full-page shimmer overlay + min 2.2s spinner ----
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
