@extends('layouts.dashboard')
@section('title', 'My Recharges')
@section('dash_compact', '1')

@section('content')

<div class="card">
  <div class="card__head">
    <h3>My Recharges</h3>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a href="{{ route('complaints') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
        <x-icon name="alert" :size="13"/> My Complaints
      </a>
      <a href="{{ route('dashboard.plans') }}" class="btn-admin btn-admin--gold btn-admin--sm">New Recharge</a>
    </div>
  </div>

  @include('partials.history-period', ['period' => $period])

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Ref</th>
          <th>Service</th>
          <th>Account</th>
          <th>Amount</th>
          <th>Cashback</th>
          <th>Wallet</th>
          <th>Status</th>
          <th>Date</th>
          <th>Receipt</th>
          <th>Complaint</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($orders as $o)
          @php
            $walletTxs = $o->wallet_txs ?? collect();
            $walletBefore = null;
            $walletAfter  = null;
            if ($walletTxs->isNotEmpty()) {
              foreach ($walletTxs as $wt) {
                $b = (float) ($wt->balance_before ?? ((float) $wt->balance_after - $wt->signedAmount()));
                $a = (float) $wt->balance_after;
                if ($walletBefore === null || $b < $walletBefore) $walletBefore = $b;
                if ($walletAfter === null  || $a > $walletAfter)  $walletAfter  = $a;
              }
            }
            $walletDelta = ($walletBefore !== null && $walletAfter !== null)
              ? ($walletAfter - $walletBefore) : 0;

            $existingComplaint = $o->complaints->isNotEmpty() ? $o->complaints->first() : null;
          @endphp
          <tr>
            <td>
              @if ($o->status === 'success')
                <a href="{{ route('recharge.invoice', $o) }}" style="color:var(--gold-500); font-weight:700;">{{ $o->reference }}</a>
              @else
                <a href="{{ route('recharge.show', $o) }}" style="color:var(--gold-500); font-weight:700;">{{ $o->reference }}</a>
              @endif
            </td>
            <td>
              @if ($o->service->logoUrl)
                <img src="{{ $o->service->logoUrl }}" alt="" style="width:22px; height:22px; object-fit:contain; vertical-align:middle; margin-right:6px;">
              @endif
              {{ $o->customerServiceName() }}
            </td>
            <td>{{ $o->account_number }}</td>
            <td><b>LKR {{ number_format($o->amount, 2) }}</b></td>
            <td>
              @if ((float) $o->profit > 0)
                <span class="tx-delta tx-delta--pos">+LKR {{ number_format($o->profit, 2) }}</span>
              @else
                <span style="color:var(--muted);">—</span>
              @endif
            </td>
            <td data-label="Wallet">
              @if ($walletTxs->isNotEmpty())
                <div class="wcell">
                  <span class="wcell__row"><small>Before</small><i>LKR {{ number_format($walletBefore, 2) }}</i></span>
                  <span class="wcell__delta {{ $walletDelta >= 0 ? 'pos' : 'neg' }}">
                    {{ $walletDelta >= 0 ? '+' : '−' }} LKR {{ number_format(abs($walletDelta), 2) }}
                  </span>
                  <span class="wcell__row"><small>After</small><i>LKR {{ number_format($walletAfter, 2) }}</i></span>
                </div>
              @else
                <span style="color:var(--muted); font-weight:600; font-size:12.5px;">—</span>
              @endif
            </td>
            <td><span class="pill pill--{{ $o->status }}">{{ $o->statusLabel() }}</span></td>
            <td><small>{{ $o->created_at->format('Y-m-d H:i') }}<br>{{ $o->created_at->diffForHumans() }}</small></td>
            <td data-label="Receipt">
              @if ($o->status === 'success' && $o->invoice_path)
                <a href="{{ route('recharge.invoice.download', $o) }}"
                   data-download
                   class="btn-admin btn-admin--ghost btn-admin--sm"
                   style="white-space:nowrap;">
                  <x-icon name="download" :size="11"/> Download
                </a>
              @elseif ($o->status === 'success')
                <a href="{{ route('recharge.invoice', $o) }}"
                   class="btn-admin btn-admin--ghost btn-admin--sm"
                   style="white-space:nowrap;">View</a>
              @elseif ($o->status === 'pending')
                <a href="{{ route('recharge.show', $o) }}"
                   class="btn-admin btn-admin--ghost btn-admin--sm"
                   style="white-space:nowrap; color:var(--muted);">
                  <x-icon name="clock" :size="11"/> Pending
                </a>
              @else
                <a href="{{ route('recharge.show', $o) }}"
                   class="btn-admin btn-admin--ghost btn-admin--sm"
                   style="white-space:nowrap; color:var(--muted);">Details</a>
              @endif
            </td>
            <td data-label="Complaint">
              @if ($existingComplaint)
                <a href="{{ route('complaints.show', $existingComplaint) }}"
                   class="btn-admin btn-admin--ghost btn-admin--sm cmp-btn"
                   style="white-space:nowrap;">
                  <span class="pill pill--{{ $existingComplaint->status }}">{{ $existingComplaint->statusLabel() }}</span>
                </a>
              @else
                <button type="button"
                        class="btn-admin btn-admin--ghost btn-admin--sm cmp-btn"
                        style="white-space:nowrap;"
                        data-complaint-btn
                        data-order-id="{{ $o->id }}"
                        data-order-ref="{{ $o->reference }}"
                        data-service="{{ $o->customerServiceName() }}"
                        data-mobile="{{ $o->account_number }}"
                        data-amount="{{ number_format((float) $o->amount, 2) }}">
                  <x-icon name="alert" :size="11"/> Complaint
                </button>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="10" style="text-align:center; padding:30px; color:var(--muted);">You haven't placed any recharges yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div style="margin-top:18px;">{{ $orders->links() }}</div>
</div>

{{-- Complaint Modal --}}
<div class="rc-modal" id="complaintModal" hidden aria-hidden="true">
  <div class="rc-modal__backdrop" data-cmp-close></div>
  <div class="rc-modal__dialog" style="max-width:520px;" role="dialog" aria-modal="true" aria-labelledby="cmpTitle">
    <div style="padding:22px 22px 8px; display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
      <div>
        <h3 id="cmpTitle" style="margin:0; font-size:20px; color:var(--navy-900);">Submit a complaint</h3>
        <p id="cmpOrderInfo" style="margin:6px 0 0; font-size:13px; color:var(--muted); font-weight:600;"></p>
      </div>
      <button type="button" class="rc-modal__close" data-cmp-close aria-label="Close">&times;</button>
    </div>
    <form id="complaintForm" style="padding:10px 22px 22px;">
      @csrf
      <input type="hidden" name="order_id" id="cmpOrderId">

      <div class="field" style="margin-bottom:12px;">
        <label>Recharged number</label>
        <input type="text" id="cmpMobile" name="mobile" class="hpr-input" readonly style="background:#f7f9fd;">
      </div>

      <div class="field" style="margin-bottom:12px;">
        <label>Subject <span class="req">*</span></label>
        <input type="text" name="subject" id="cmpSubject" required maxlength="160"
               class="hpr-input" placeholder="e.g. Recharge not received">
      </div>

      <div class="field" style="margin-bottom:12px;">
        <label>What went wrong? <span class="req">*</span></label>
        <textarea name="reason" id="cmpReason" required minlength="10" maxlength="2000" rows="5"
                  class="hpr-input hpr-input--ta"
                  placeholder="Please explain the issue (min 10 characters). Include any error messages or details so we can help faster."></textarea>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
        <button type="button" class="btn-admin btn-admin--ghost btn-admin--sm" data-cmp-close>Cancel</button>
        <button type="submit" class="btn-admin btn-admin--gold btn-admin--sm" id="cmpSubmit">
          <span class="btn-label"><x-icon name="send" :size="13"/> Submit Complaint</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('styles')
<style>
.cmp-btn{min-width:92px;}
.rc-modal__dialog h3{font-weight:800; letter-spacing:-.01em;}
.field label{
  display:block; margin-bottom:6px; font-size:12px; font-weight:700;
  color:var(--navy-800); text-transform:uppercase; letter-spacing:.06em;
}
.field .req{color:#b42f2f;}
</style>
@endpush

@push('scripts')
<script>
(function(){
  var modal = document.getElementById('complaintModal');
  if (!modal) return;
  // Move modal to <body> so it sits outside any CSS-transformed ancestor
  // (required for safe fixed/absolute positioning of the backdrop).
  document.body.appendChild(modal);
  var form    = document.getElementById('complaintForm');
  var orderId = document.getElementById('cmpOrderId');
  var mobile  = document.getElementById('cmpMobile');
  var subject = document.getElementById('cmpSubject');
  var reason  = document.getElementById('cmpReason');
  var info    = document.getElementById('cmpOrderInfo');
  var submit  = document.getElementById('cmpSubmit');

  function open(){
    modal.hidden = false;
    modal.setAttribute('aria-hidden','false');
    setTimeout(function(){ subject.focus(); }, 80);
  }
  function close(){
    modal.hidden = true;
    modal.setAttribute('aria-hidden','true');
    form.reset();
  }

  modal.addEventListener('click', function(e){
    if (e.target.closest('[data-cmp-close]')) close();
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && !modal.hidden) close();
  });

  document.querySelectorAll('[data-complaint-btn]').forEach(function(btn){
    btn.addEventListener('click', function(){
      orderId.value = btn.dataset.orderId;
      mobile.value  = btn.dataset.mobile;
      info.textContent = btn.dataset.service + ' · ' + btn.dataset.orderRef + ' · LKR ' + btn.dataset.amount;
      subject.value = '';
      reason.value  = '';
      open();
    });
  });

  form.addEventListener('submit', function(e){
    e.preventDefault();
    if (typeof setBtnLoading === 'function') setBtnLoading(submit, true);
    else { submit.disabled = true; var sp = submit.querySelector('.btn-spinner'); if(sp) sp.hidden=false; }

    var started = performance.now();
    var fd = new FormData(form);
    fetch('{{ route('complaints.store') }}', {
      method:'POST', body:fd,
      headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
      credentials:'same-origin'
    })
    .then(function(r){ return r.json().then(function(d){ return {ok:r.ok, d:d}; }); })
    .then(function(res){
      var elapsed = performance.now() - started;
      var wait = Math.max(0, 2200 - elapsed);
      setTimeout(function(){
        if (typeof setBtnLoading === 'function') setBtnLoading(submit, false);
        else { submit.disabled = false; var sp = submit.querySelector('.btn-spinner'); if(sp) sp.hidden=true; }
        if (res.ok && res.d.ok){
          if (window.toast) window.toast(res.d.message || 'Complaint submitted!', 'success');
          setTimeout(function(){
            if (res.d.redirect) window.location.href = res.d.redirect;
            else window.location.reload();
          }, 500);
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
      var wait = Math.max(0, 2200 - elapsed);
      setTimeout(function(){
        if (typeof setBtnLoading === 'function') setBtnLoading(submit, false);
        else { submit.disabled = false; var sp = submit.querySelector('.btn-spinner'); if(sp) sp.hidden=true; }
        if (window.toast) window.toast(err.message || 'Network error', 'error');
      }, wait);
    });
  });
})();
</script>
@endpush
