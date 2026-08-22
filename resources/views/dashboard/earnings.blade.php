@extends('layouts.dashboard')
@section('title', 'My Earnings')
@section('dash_compact', '1')

@section('content')

{{-- Hero --}}
<div class="earn-hero">
  <div class="earn-hero__top">
    <div>
      <small>Total Cashback Earned</small>
      <b>LKR {{ number_format($totalEarned, 2) }}</b>
    </div>
    <div class="earn-hero__actions">
      <a href="{{ route('wallet') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
        <x-icon name="wallet" :size="14"/> My Wallet
      </a>
      <a href="{{ route('refunds') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
        <x-icon name="check-circle" :size="14"/> Refunds
      </a>
      <a href="{{ route('dashboard.plans') }}" class="btn-admin btn-admin--gold btn-admin--sm">
        <x-icon name="bolt-nav" :size="14"/> Earn more
      </a>
    </div>
  </div>
  <div class="earn-hero__grid">
    <div class="earn-stat">
      <span>This month</span>
      <b>LKR {{ number_format($thisMonth, 2) }}</b>
    </div>
    <div class="earn-stat">
      <span>Current wallet balance</span>
      <b>LKR {{ number_format($wallet->balance, 2) }}</b>
    </div>
    <div class="earn-stat earn-stat--muted">
      <span>In selected date range</span>
      <b>+LKR {{ number_format($filteredTotal, 2) }}</b>
    </div>
  </div>
</div>

{{-- Cashback list --}}
<div class="card">
  <div class="card__head">
    <h3>Cashback History</h3>
    <span style="color:var(--muted); font-weight:600; font-size:13px;">
      {{ $earnings->total() }} {{ Str::plural('reward', $earnings->total()) }}
    </span>
  </div>

  <form method="GET" action="{{ route('earnings') }}" class="earn-filters" id="earnFilters">
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
      <a href="{{ route('earnings') }}" class="btn-admin btn-admin--ghost btn-admin--sm">Clear</a>
    </div>
  </form>

  <div class="table-wrap" style="margin-top:18px;">
    <table class="data-table earn-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>From</th>
          <th>Description</th>
          <th style="text-align:right;">Cashback</th>
          <th style="text-align:right;">Wallet After</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($earnings as $e)
          @php
            $svcName = null;
            if ($e->transactable && method_exists($e->transactable, 'getAttribute')) {
              $svcName = optional($e->transactable->service ?? null)->name;
            }
          @endphp
          <tr>
            <td>
              <div style="font-weight:700; color:var(--navy-900); font-size:13px;">
                {{ $e->created_at->timezone('Asia/Colombo')->format('d M Y') }}
              </div>
              <small style="color:var(--muted);">{{ $e->created_at->timezone('Asia/Colombo')->format('h:i A') }}</small>
            </td>
            <td>
              @if ($svcName)
                <div style="font-weight:700; color:var(--navy-900);">{{ $svcName }}</div>
                @if ($e->transactable && $e->transactable->account_number)
                  <small style="color:var(--muted);">{{ $e->transactable->account_number }}</small>
                @endif
              @else
                <span style="color:var(--muted);">—</span>
              @endif
            </td>
            <td style="max-width:300px;">{{ $e->description ?: 'Cashback reward' }}</td>
            <td style="text-align:right;">
              <span class="tx-delta tx-delta--pos">+LKR {{ number_format((float) $e->amount, 2) }}</span>
            </td>
            <td style="text-align:right;">
              <b style="color:var(--navy-900);">LKR {{ number_format((float) $e->balance_after, 2) }}</b>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center; padding:40px; color:var(--muted);">
              No cashback earned yet. Place successful recharges to earn rewards 🎁
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div style="margin-top:18px;">{{ $earnings->links() }}</div>
</div>

@endsection

@push('styles')
<style>
.earn-hero{
  background:linear-gradient(135deg,#0b2a5b,#071b3d);
  color:#fff; border-radius:20px; padding:22px 26px;
  margin-bottom:20px; position:relative; overflow:hidden;
}
.earn-hero::before{
  content:"";position:absolute;right:-60px;top:-60px;width:260px;height:260px;
  background:radial-gradient(circle,rgba(232,163,23,.30),transparent 70%);
  border-radius:50%;
}
.earn-hero__top{
  display:flex; align-items:flex-start; justify-content:space-between; gap:16px;
  position:relative; flex-wrap:wrap;
}
.earn-hero__top small{
  display:block; font-size:12px; letter-spacing:.14em; text-transform:uppercase;
  opacity:.8; font-weight:700; margin-bottom:6px;
}
.earn-hero__top b{font-size:32px; font-weight:800; letter-spacing:-.02em; display:block;}
.earn-hero__actions{display:flex; gap:8px; flex-wrap:wrap;}
.earn-hero__actions .btn-admin--ghost{
  background:rgba(255,255,255,.08); border-color:transparent; color:#fff;
}
.earn-hero__actions .btn-admin--ghost:hover{background:rgba(255,255,255,.18);}
.earn-hero__grid{
  display:grid; grid-template-columns:repeat(3,1fr); gap:12px;
  margin-top:20px; position:relative;
}
.earn-stat{
  background:rgba(255,255,255,.08); border-radius:14px; padding:14px 16px;
  border:1px solid rgba(255,255,255,.08);
}
.earn-stat span{display:block; font-size:12px; opacity:.8; font-weight:600; margin-bottom:4px;}
.earn-stat b{font-size:18px; font-weight:800; color:#fff;}
.earn-stat--muted b{color:var(--gold-300);}

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
  .earn-hero__grid{grid-template-columns:1fr 1fr;}
  .earn-stat:last-child{grid-column:1/-1;}
}
@media (max-width:560px){
  .earn-hero{padding:14px 14px; border-radius:14px; margin-bottom:12px;}
  .earn-hero__top b{font-size:22px;}
  .earn-hero__grid{grid-template-columns:1fr 1fr; gap:8px; margin-top:12px;}
  .earn-stat:last-child{grid-column:1 / -1;}
  .earn-stat{padding:10px 12px;}
  .earn-stat b{font-size:15px;}
  .earn-filters{grid-template-columns:1fr; gap:10px;}
  .earn-filters__btns{justify-content:stretch;}
  .earn-filters__btns .btn-admin{flex:1;}
}
</style>
@endpush

@push('scripts')
<script>
/* Date range guard */
(function(){
  var fromEl = document.querySelector('.earn-filters input[name=from]');
  var toEl   = document.querySelector('.earn-filters input[name=to]');
  if (fromEl && toEl){
    fromEl.addEventListener('change', function(){ if (toEl.value && fromEl.value > toEl.value) toEl.value = fromEl.value; });
    toEl.addEventListener('change', function(){ if (fromEl.value && toEl.value < fromEl.value) fromEl.value = toEl.value; });
  }
})();
</script>
@endpush
