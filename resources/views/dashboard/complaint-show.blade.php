@extends('layouts.dashboard')
@section('title', 'Complaint ' . $complaint->reference)
@section('dash_compact', '1')

@section('content')

<div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
  <a href="{{ route('complaints') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
    <x-icon name="caret" :size="11" style="transform:rotate(90deg); margin-right:4px;"/> Back
  </a>
  <span class="pill {{ $complaint->statusBadgeClass() }}">{{ $complaint->statusLabel() }}</span>
</div>

<div class="card cmp-detail">
  <div class="cmp-detail__head">
    <div>
      <small>Complaint Reference</small>
      <h2 style="margin:0; font-size:22px; color:var(--gold-500); letter-spacing:.02em;">{{ $complaint->reference }}</h2>
      <small style="color:var(--muted);">Opened {{ $complaint->created_at->timezone('Asia/Colombo')->format('d M Y · h:i A') }}</small>
    </div>
    @if($complaint->order)
      <a href="{{ route('recharge.show', $complaint->order) }}" class="btn-admin btn-admin--ghost btn-admin--sm" style="white-space:nowrap;">
        <x-icon name="bill" :size="12"/> View Order
      </a>
    @endif
  </div>

  <div class="cmp-detail__grid">
    <div class="cmp-kv">
      <span>Subject</span>
      <b>{{ $complaint->subject }}</b>
    </div>
    <div class="cmp-kv">
      <span>Mobile / Account</span>
      <b>{{ $complaint->mobile ?: '—' }}</b>
    </div>
    @if($complaint->order)
      <div class="cmp-kv">
        <span>Order Reference</span>
        <b><a href="{{ route('recharge.show', $complaint->order) }}" style="color:var(--gold-500); text-decoration:none;">{{ $complaint->order->reference }}</a></b>
      </div>
      <div class="cmp-kv">
        <span>Service</span>
        <b>{{ $complaint->order->customerServiceName() }}</b>
      </div>
      <div class="cmp-kv">
        <span>Order Amount</span>
        <b>LKR {{ number_format((float) $complaint->order->amount, 2) }}</b>
      </div>
      <div class="cmp-kv">
        <span>Order Status</span>
        <b><span class="pill pill--{{ $complaint->order->status }}">{{ $complaint->order->statusLabel() }}</span></b>
      </div>
    @endif
  </div>

  <div class="cmp-msg cmp-msg--customer">
    <div class="cmp-msg__head">
      <img src="{{ auth()->user()->avatarUrl(32) }}" alt="">
      <div>
        <b>{{ auth()->user()->name }}</b>
        <small>{{ $complaint->created_at->timezone('Asia/Colombo')->format('d M Y · h:i A') }}</small>
      </div>
    </div>
    <p>{{ $complaint->reason }}</p>
  </div>

  @if($complaint->admin_note)
    <div class="cmp-msg cmp-msg--admin">
      <div class="cmp-msg__head">
        <div style="width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,var(--gold-500),var(--gold-700,#c07d0b)); display:grid; place-items:center; color:#fff; font-weight:800;">S</div>
        <div>
          <b>Support{{ $complaint->handler ? ' · ' . $complaint->handler->name : '' }}</b>
          <small>
            @if($complaint->resolved_at)
              {{ $complaint->resolved_at->timezone('Asia/Colombo')->format('d M Y · h:i A') }}
            @else
              Replied
            @endif
          </small>
        </div>
      </div>
      <p>{{ $complaint->admin_note }}</p>
      @if($complaint->status === 'resolved')
        <div class="cmp-resolved">
          <x-icon name="check-circle" :size="18"/> Resolved — thanks for your patience!
        </div>
      @elseif($complaint->status === 'rejected')
        <div class="cmp-rejected">
          <x-icon name="x" :size="18"/> Closed
        </div>
      @endif
    </div>
  @else
    <div class="cmp-pending">
      <div class="spin" style="width:20px; height:20px; border-width:2.5px;"></div>
      <span>Awaiting review from our support team.</span>
    </div>
  @endif
</div>

@endsection

@push('styles')
<style>
.cmp-detail__head{
  display:flex; align-items:flex-start; justify-content:space-between; gap:14px;
  padding-bottom:18px; border-bottom:1px solid var(--line); margin-bottom:18px; flex-wrap:wrap;
}
.cmp-detail__head small{display:block; color:var(--muted); font-weight:700; letter-spacing:.08em; text-transform:uppercase; font-size:11px; margin-bottom:6px;}

.cmp-detail__grid{
  display:grid; grid-template-columns:repeat(3,1fr); gap:14px 20px; margin-bottom:22px;
}
.cmp-kv span{
  display:block; font-size:11px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px;
}
.cmp-kv b{font-size:14px; color:var(--navy-900);}

.cmp-msg{
  border-radius:14px; padding:16px 18px; margin-bottom:14px;
  border:1.6px solid var(--line); background:#f7f9fd;
}
.cmp-msg--customer{background:#fff6df; border-color:#f1d99a;}
.cmp-msg--admin{background:#f0f6ff; border-color:#c5d7f5;}
.cmp-msg__head{
  display:flex; align-items:center; gap:10px; margin-bottom:10px;
}
.cmp-msg__head img{
  width:32px; height:32px; border-radius:50%; object-fit:cover;
}
.cmp-msg__head b{display:block; font-size:13px; color:var(--navy-900);}
.cmp-msg__head small{display:block; font-size:11px; color:var(--muted); font-weight:600;}
.cmp-msg p{margin:0; color:var(--navy-900); font-size:14px; line-height:1.6; white-space:pre-wrap; word-break:break-word;}

.cmp-resolved, .cmp-rejected{
  margin-top:14px; padding:10px 14px; border-radius:10px; font-weight:700; font-size:13px;
  display:flex; align-items:center; gap:8px;
}
.cmp-resolved{background:#e5f7ec; color:#186b35;}
.cmp-rejected{background:#fdecec; color:#8a1f1f;}

.cmp-pending{
  display:flex; align-items:center; gap:10px; padding:14px 16px;
  border-radius:12px; background:#fff8ec; border:1px dashed var(--gold-500);
  color:#7a5100; font-weight:600; font-size:13px;
}

@media (max-width:720px){
  .cmp-detail__grid{grid-template-columns:1fr 1fr;}
}
@media (max-width:440px){
  .cmp-detail__grid{grid-template-columns:1fr;}
}
</style>
@endpush
