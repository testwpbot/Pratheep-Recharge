@extends('layouts.dashboard')
@section('title', 'My Complaints')
@section('dash_compact', '1')

@section('content')

<div class="cmp-hero">
  <div>
    <small>Your support tickets</small>
    <b>Complaints</b>
  </div>
  <p>Have an issue with a recharge? Open a complaint directly from the <a href="{{ route('recharge.history') }}" style="color:#fff; text-decoration:underline; font-weight:700;">My Recharges</a> page — we'll review and reply within 24 hours.</p>
</div>

<div class="card">
  <div class="card__head">
    <h3>Complaints</h3>
    <a href="{{ route('recharge.history') }}" class="btn-admin btn-admin--gold btn-admin--sm">
      <x-icon name="bill" :size="13"/> My Recharges
    </a>
  </div>

  @include('partials.history-period', ['period' => $period, 'keep' => ['status' => $status]])

  {{-- Status tabs --}}
  <div class="cmp-tabs">
    @foreach ([
      'all'         => 'All (' . ($counts['open'] + $counts['progress'] + $counts['resolved'] + $counts['rejected']) . ')',
      'open'        => 'Open (' . $counts['open'] . ')',
      'in_progress' => 'In Progress (' . $counts['progress'] . ')',
      'resolved'    => 'Resolved (' . $counts['resolved'] . ')',
      'rejected'    => 'Rejected (' . $counts['rejected'] . ')',
    ] as $s => $label)
      <a href="{{ route('complaints', ['status' => $s] + request()->except('status','page')) }}"
         class="cmp-tab {{ $status === $s ? 'is-active' : '' }}">{{ $label }}</a>
    @endforeach
  </div>

  @if ($complaints->count())
    <div class="cmp-list">
      @foreach ($complaints as $c)
        <a href="{{ route('complaints.show', $c) }}" class="cmp-row">
          <div class="cmp-row__top">
            <b>{{ $c->reference }}</b>
            <span class="pill {{ $c->statusBadgeClass() }}">{{ $c->statusLabel() }}</span>
          </div>
          <div class="cmp-row__mid">{{ $c->subject }}</div>
          <div class="cmp-row__meta">
            <span><x-icon name="bill" :size="12"/> {{ $c->order?->reference ?: '—' }}</span>
            @if ($c->mobile)
              <span><x-icon name="phone" :size="12"/> {{ $c->mobile }}</span>
            @endif
            <span><x-icon name="clock" :size="12"/> {{ $c->created_at->timezone('Asia/Colombo')->format('d M Y · h:i A') }}</span>
          </div>
        </a>
      @endforeach
    </div>
  @else
    <div class="cmp-empty">
      <x-icon name="check-circle" :size="48" style="color:var(--green); margin-bottom:8px;"/>
      <b style="font-size:16px; color:var(--navy-900);">{{ $period->emptyMessage('complaints') }}</b>
      <small style="color:var(--muted); margin-top:4px;">If you run into an issue with a recharge, you can raise a complaint from the My Recharges page.</small>
    </div>
  @endif

  <div style="margin-top:18px;">{{ $complaints->links() }}</div>
</div>

@endsection

@push('styles')
<style>
.cmp-hero{
  background:linear-gradient(135deg,#7f1d1d,#b91c1c);
  color:#fff; border-radius:20px; padding:22px 26px;
  margin-bottom:20px; position:relative; overflow:hidden;
}
.cmp-hero::before{
  content:"";position:absolute;right:-60px;top:-60px;width:260px;height:260px;
  background:radial-gradient(circle,rgba(255,200,200,.25),transparent 70%);
  border-radius:50%;
}
.cmp-hero small{
  display:block; font-size:12px; letter-spacing:.14em; text-transform:uppercase;
  opacity:.85; font-weight:700; margin-bottom:6px;
}
.cmp-hero b{font-size:28px; font-weight:800; letter-spacing:-.02em; display:block;}
.cmp-hero p{margin:10px 0 0; font-size:13.5px; opacity:.9; max-width:620px; line-height:1.5; position:relative;}

.cmp-list{display:flex; flex-direction:column; gap:12px;}
.cmp-row{
  display:block; padding:16px 18px; border-radius:14px;
  border:1.6px solid var(--line); text-decoration:none; color:inherit;
  transition:border-color .15s ease, transform .12s ease, box-shadow .15s ease;
}
.cmp-row:hover{
  border-color:var(--gold-500);
  box-shadow:0 8px 24px rgba(11,42,91,.08);
  transform:translateY(-1px);
}
.cmp-row__top{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  margin-bottom:6px;
}
.cmp-row__top b{font-size:14px; color:var(--gold-500); font-family:var(--mono,ui-monospace,monospace); letter-spacing:.02em;}
.cmp-row__mid{font-weight:700; color:var(--navy-900); font-size:15px; margin-bottom:8px;}
.cmp-row__meta{
  display:flex; flex-wrap:wrap; gap:14px;
  font-size:12px; color:var(--muted); font-weight:600;
}
.cmp-row__meta span{display:inline-flex; align-items:center; gap:4px;}

.cmp-empty{
  text-align:center; padding:50px 20px; display:flex; flex-direction:column; align-items:center;
}
@media (max-width:560px){
  .cmp-hero{padding:14px 14px; border-radius:14px; margin-bottom:12px;}
  .cmp-hero b{font-size:22px;}
  .cmp-hero p{margin-top:6px; font-size:13px;}
  .cmp-tabs{padding:0 0 12px; margin-bottom:12px; gap:6px;}
}
</style>
@endpush
