@extends('layouts.dashboard')
@section('title', 'My Refunds')

@section('content')

{{-- Hero --}}
<div class="ref-hero">
  <div class="ref-hero__top">
    <div>
      <small>Total Refunded</small>
      <b>LKR {{ number_format($totalRefunded, 2) }}</b>
    </div>
    <div class="ref-hero__actions">
      <a href="{{ route('wallet') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
        <x-icon name="wallet" :size="14"/> My Wallet
      </a>
      <a href="{{ route('earnings') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
        <x-icon name="gift-dr" :size="14"/> Earnings
      </a>
      <a href="{{ route('dashboard.plans') }}" class="btn-admin btn-admin--gold btn-admin--sm">
        <x-icon name="bolt-nav" :size="14"/> New recharge
      </a>
    </div>
  </div>
  <div class="ref-hero__grid">
    <div class="ref-stat">
      <span>This month</span>
      <b>LKR {{ number_format($thisMonth, 2) }}</b>
    </div>
    <div class="ref-stat">
      <span>Current wallet balance</span>
      <b>LKR {{ number_format($wallet->balance, 2) }}</b>
    </div>
    <div class="ref-stat ref-stat--muted">
      <span>In selected date range</span>
      <b>+LKR {{ number_format($filteredTotal, 2) }}</b>
    </div>
  </div>
</div>

{{-- Refund list --}}
<div class="card">
  <div class="card__head">
    <h3>Refund History</h3>
    <span style="color:var(--muted); font-weight:600; font-size:13px;">
      {{ $refunds->total() }} {{ Str::plural('refund', $refunds->total()) }}
    </span>
  </div>

  <form method="GET" action="{{ route('refunds') }}" class="earn-filters" id="refFilters">
    <div class="field">
      <label>From date</label>
      <input type="date" name="from" value="{{ $from }}" class="hpr-input">
    </div>
    <div class="field">
      <label>To date</label>
      <input type="date" name="to" value="{{ $to }}" class="hpr-input">
    </div>
    <div class="earn-filters__btns">
      <button type="submit" class="btn-admin btn-admin--gold btn-admin--sm">
        <x-icon name="search" :size="13"/> Filter
      </button>
      <a href="{{ route('refunds') }}" class="btn-admin btn-admin--ghost btn-admin--sm">Clear</a>
    </div>
  </form>

  <div class="table-wrap" style="margin-top:18px;">
    <table class="data-table earn-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Order / Service</th>
          <th>Reason</th>
          <th style="text-align:right;">Refunded</th>
          <th style="text-align:right;">Wallet After</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($refunds as $r)
          @php
            $order = ($r->transactable && is_a($r->transactable, \App\Models\Order::class)) ? $r->transactable : null;
            $svcName = $order?->service?->name;
          @endphp
          <tr>
            <td>
              <div style="font-weight:700; color:var(--navy-900); font-size:13px;">
                {{ $r->created_at->timezone('Asia/Colombo')->format('d M Y') }}
              </div>
              <small style="color:var(--muted);">{{ $r->created_at->timezone('Asia/Colombo')->format('h:i A') }}</small>
            </td>
            <td>
              @if ($order)
                <div style="font-weight:700; color:var(--navy-900);">
                  <a href="{{ route('recharge.show', $order) }}" style="color:var(--gold-500); text-decoration:none;">
                    {{ $order->reference }}
                  </a>
                </div>
                <small style="color:var(--muted);">
                  {{ $svcName ?? 'Recharge' }}
                  @if ($order->account_number) · {{ $order->account_number }} @endif
                </small>
              @else
                <span style="color:var(--muted);">—</span>
              @endif
            </td>
            <td style="max-width:300px;">{{ $r->description ?: 'Refunded to wallet' }}</td>
            <td style="text-align:right;">
              <span class="tx-delta tx-delta--pos">+LKR {{ number_format((float) $r->amount, 2) }}</span>
            </td>
            <td style="text-align:right;">
              <b style="color:var(--navy-900);">LKR {{ number_format((float) $r->balance_after, 2) }}</b>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center; padding:40px; color:var(--muted);">
              No refunds yet — that's a good thing! 👍
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div style="margin-top:18px;">{{ $refunds->links() }}</div>
</div>

@endsection

@push('styles')
<style>
.ref-hero{
  background:linear-gradient(135deg,#1e3a8a,#0b2a5b);
  color:#fff; border-radius:20px; padding:22px 26px;
  margin-bottom:20px; position:relative; overflow:hidden;
}
.ref-hero::before{
  content:"";position:absolute;right:-60px;top:-60px;width:260px;height:260px;
  background:radial-gradient(circle,rgba(96,165,250,.28),transparent 70%);
  border-radius:50%;
}
.ref-hero__top{
  display:flex; align-items:flex-start; justify-content:space-between; gap:16px;
  position:relative; flex-wrap:wrap;
}
.ref-hero__top small{
  display:block; font-size:12px; letter-spacing:.14em; text-transform:uppercase;
  opacity:.8; font-weight:700; margin-bottom:6px;
}
.ref-hero__top b{font-size:32px; font-weight:800; letter-spacing:-.02em; display:block;}
.ref-hero__actions{display:flex; gap:8px; flex-wrap:wrap;}
.ref-hero__actions .btn-admin--ghost{
  background:rgba(255,255,255,.08); border-color:transparent; color:#fff;
}
.ref-hero__actions .btn-admin--ghost:hover{background:rgba(255,255,255,.18);}
.ref-hero__grid{
  display:grid; grid-template-columns:repeat(3,1fr); gap:12px;
  margin-top:20px; position:relative;
}
.ref-stat{
  background:rgba(255,255,255,.08); border-radius:14px; padding:14px 16px;
  border:1px solid rgba(255,255,255,.08);
}
.ref-stat span{display:block; font-size:12px; opacity:.8; font-weight:600; margin-bottom:4px;}
.ref-stat b{font-size:18px; font-weight:800; color:#fff;}
.ref-stat--muted b{color:#93c5fd;}

.earn-filters{
  display:grid; grid-template-columns:1fr 1fr auto;
  gap:14px; align-items:end; margin-top:8px;
}
.earn-filters__btns{display:flex; gap:8px; align-items:center;}
.earn-filters .field label{
  display:block; margin-bottom:6px; font-size:12px; font-weight:700;
  color:var(--navy-800); text-transform:uppercase; letter-spacing:.08em;
}

.earn-table td, .earn-table th{vertical-align:middle;}

@media (max-width:880px){
  .earn-filters{grid-template-columns:1fr 1fr;}
  .earn-filters__btns{grid-column:1 / -1; justify-content:flex-end;}
  .ref-hero__grid{grid-template-columns:1fr 1fr;}
  .ref-stat:last-child{grid-column:1/-1;}
}
@media (max-width:560px){
  .ref-hero{padding:18px 18px;}
  .ref-hero__top b{font-size:26px;}
  .ref-hero__grid{grid-template-columns:1fr;}
  .earn-filters{grid-template-columns:1fr;}
  .earn-filters__btns{justify-content:stretch;}
  .earn-filters__btns .btn-admin{flex:1;}
}
</style>
@endpush

@push('scripts')
<script>
(function(){
  var form = document.getElementById('refFilters');
  if (form){
    var fromEl = form.querySelector('input[name=from]');
    var toEl   = form.querySelector('input[name=to]');
    if (fromEl && toEl){
      fromEl.addEventListener('change', function(){ if (toEl.value && fromEl.value > toEl.value) toEl.value = fromEl.value; });
      toEl.addEventListener('change', function(){ if (fromEl.value && toEl.value < fromEl.value) fromEl.value = toEl.value; });
    }
  }
})();
</script>
@endpush
