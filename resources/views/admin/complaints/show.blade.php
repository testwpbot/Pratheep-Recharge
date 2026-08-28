@extends('layouts.admin')
@section('title', 'Complaint ' . $complaint->reference)

@section('content')

<div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
  <a href="{{ route('admin.complaints.index') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
    <x-icon name="caret" :size="11" style="transform:rotate(90deg); margin-right:4px;"/> All complaints
  </a>
  <span class="pill {{ $complaint->statusBadgeClass() }}">{{ $complaint->statusLabel() }}</span>
</div>

<div class="card" style="padding:0; overflow:hidden;">
  <div class="adm-cmp-head">
    <div>
      <small>Complaint</small>
      <h2 style="margin:0; font-size:22px; color:var(--gold-500);">{{ $complaint->reference }}</h2>
      <div style="margin-top:6px; font-size:13px; color:var(--muted); font-weight:600;">
        Opened {{ $complaint->created_at->timezone('Asia/Colombo')->format('d M Y · h:i A') }}
        @if($complaint->handler) · by {{ $complaint->user->name }} · handled by {{ $complaint->handler->name }} @endif
      </div>
    </div>
    <div style="text-align:right;">
      @if($complaint->order)
        <a href="{{ route('admin.orders.show', $complaint->order) }}" class="btn-admin btn-admin--ghost btn-admin--sm">
          <x-icon name="bill" :size="12"/> View order
        </a>
      @endif
    </div>
  </div>

  <div style="padding:22px;">
    <div class="adm-cmp-grid">
      <div class="kv"><span>Customer</span><b>{{ $complaint->user->name }}<br><small style="color:var(--muted); font-weight:500;">{{ $complaint->user->email }}</small></b></div>
      <div class="kv"><span>Subject</span><b>{{ $complaint->subject }}</b></div>
      <div class="kv"><span>Mobile / Account</span><b>{{ $complaint->mobile ?: '—' }}</b></div>
      @if($complaint->order)
        <div class="kv"><span>Order Ref</span><b><a href="{{ route('admin.orders.show', $complaint->order) }}" style="color:var(--gold-500); text-decoration:none;">{{ $complaint->order->reference }}</a></b></div>
        <div class="kv"><span>Service</span><b>{{ optional($complaint->order->service)->name }} ({{ optional($complaint->order->provider)->name }})</b></div>
        <div class="kv"><span>Order Amount</span><b>LKR {{ number_format((float) $complaint->order->amount, 2) }}</b></div>
        <div class="kv"><span>Order Status</span><b><span class="pill pill--{{ $complaint->order->status }}">{{ $complaint->order->statusLabel() }}</span></b></div>
      @endif
    </div>

    <div class="thread">
      <div class="msg msg--customer">
        <div class="msg__head">
          <img src="{{ $complaint->user->avatarUrl(36) }}" alt="">
          <div>
            <b>{{ $complaint->user->name }}</b>
            <small>{{ $complaint->created_at->timezone('Asia/Colombo')->format('d M Y · h:i A') }}</small>
          </div>
        </div>
        <div class="msg__body">{{ $complaint->reason }}</div>
      </div>

      @if($complaint->admin_note)
        <div class="msg msg--admin">
          <div class="msg__head">
            <div class="msg__avatar" style="background:linear-gradient(135deg,var(--gold-500),#c07d0b);">S</div>
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
          <div class="msg__body">{{ $complaint->admin_note }}</div>
        </div>
      @endif
    </div>

    {{-- Reply form --}}
    <form method="POST" action="{{ route('admin.complaints.reply', $complaint) }}" class="reply-form" data-ajax>
      @csrf
      <h4 style="margin:10px 0 10px; font-size:15px; color:var(--navy-900);">Reply / Update status</h4>
      <div style="display:grid; grid-template-columns:1fr; gap:12px; margin-bottom:12px;">
        <textarea name="admin_note" rows="4" class="hpr-input hpr-input--ta" placeholder="Type your reply to the customer here. They will get an email notification." required>{{ $complaint->admin_note }}</textarea>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:space-between; align-items:center;">
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          @foreach ([
            'open'        => 'Mark Open',
            'in_progress' => 'In Progress',
            'resolved'    => 'Resolve',
            'rejected'    => 'Reject',
          ] as $s => $lbl)
            <label class="status-radio">
              <input type="radio" name="status" value="{{ $s }}" {{ $complaint->status === $s ? 'checked' : '' }}>
              <span>{{ $lbl }}</span>
            </label>
          @endforeach
        </div>
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="send" :size="13"/> Send Reply</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('styles')
<style>
.adm-cmp-head{
  padding:22px; border-bottom:1px solid var(--line);
  display:flex; align-items:flex-start; justify-content:space-between; gap:14px; flex-wrap:wrap;
}
.adm-cmp-head small{display:block; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.08em; font-size:11px; margin-bottom:6px;}
.adm-cmp-grid{
  display:grid; grid-template-columns:repeat(3,1fr); gap:14px 22px; margin-bottom:22px;
}
.kv span{display:block; font-size:11px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px;}
.kv b{font-size:14px; color:var(--navy-900); line-height:1.5;}

.thread{display:flex; flex-direction:column; gap:14px; margin-bottom:24px;}
.msg{border-radius:14px; padding:16px 18px; border:1.6px solid var(--line);}
.msg--customer{background:#fff6df; border-color:#f1d99a;}
.msg--admin{background:#f0f6ff; border-color:#c5d7f5;}
.msg__head{display:flex; align-items:center; gap:10px; margin-bottom:10px;}
.msg__head img, .msg__avatar{
  width:36px; height:36px; border-radius:50%; object-fit:cover; flex:none;
  display:grid; place-items:center; color:#fff; font-weight:800;
}
.msg__head b{display:block; font-size:13px; color:var(--navy-900);}
.msg__head small{display:block; font-size:11px; color:var(--muted); font-weight:600;}
.msg__body{color:var(--navy-900); font-size:14px; line-height:1.65; white-space:pre-wrap; word-break:break-word;}

.status-radio{
  display:inline-flex; align-items:center; gap:6px;
  padding:8px 12px; border-radius:999px;
  border:1.6px solid var(--line); background:#fff;
  font-weight:700; font-size:12px; color:var(--navy-900);
  cursor:pointer; transition:background .15s ease, border-color .15s ease;
}
.status-radio input{accent-color:var(--gold-500); margin:0;}
.status-radio:hover{background:#f7f9fd;}

@media (max-width:720px){
  .adm-cmp-grid{grid-template-columns:1fr 1fr;}
}
@media (max-width:440px){
  .adm-cmp-grid{grid-template-columns:1fr;}
}
</style>
@endpush
