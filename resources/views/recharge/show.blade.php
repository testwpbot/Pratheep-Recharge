@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')
@section('title', "Order {$order->reference}")
@section('dash_compact', '1')

@section('content')

@auth
  <a href="{{ route('recharge.history') }}" class="btn-admin btn-admin--ghost btn-admin--sm" style="margin-bottom:18px; display:inline-flex;">← My Orders</a>
@else
  <section class="sec sec--light"><div class="wrap" style="max-width:720px;">
@endauth

<div class="card" @if(!auth()->check()) style="margin-bottom:40px;" @endif>
  <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:22px; flex-wrap:wrap;">
    <div>
      <h2 style="margin:0; font-size:22px; font-weight:800; color:var(--navy-900); letter-spacing:-.02em;">Order {{ $order->reference }}</h2>
      <p style="margin:4px 0 0; color:var(--muted); font-size:14px;">Placed {{ $order->created_at->format('M d, Y · H:i') }}</p>
    </div>
    <div class="order-status order-status--{{ $order->status }}">
      <span class="dot"></span>
      {{ $order->statusLabel() }}
    </div>
  </div>

  <div style="display:flex; align-items:center; gap:16px; padding:14px; border-radius:14px; background:#f7f9fd; margin-bottom:22px;">
    <img src="{{ $order->service->logo ? asset($order->service->logo) : asset('assets/logo-mark.png') }}"
         alt="{{ $order->customerServiceName() }}" style="width:54px; height:54px; object-fit:contain;">
    <div>
      <div style="font-weight:800; color:var(--navy-900);">{{ $order->customerServiceName() }}</div>
      <div style="color:var(--muted); font-size:13px;">Account: <b>{{ $order->account_number }}</b></div>
    </div>
    <span style="margin-left:auto; font-size:22px; font-weight:800; color:var(--navy-900);">LKR {{ number_format($order->totalPaid(), 2) }}</span>
  </div>

  @if ($order->hasFee())
    <div style="margin-top:-6px; margin-bottom:6px; font-size:13px; color:var(--muted); text-align:right;">
      Bill LKR {{ number_format($order->amount, 2) }} + service fee LKR {{ number_format($order->feeAmount(), 2) }}
    </div>
  @endif

  @if ($order->isRefunded())
    <div class="alert alert--success">
      {{ $order->publicMessage() }}
    </div>
  @elseif (in_array($order->status, ['pending', 'processing'], true))
    <div class="alert alert--success">
      {{ $order->publicMessage() }}
    </div>
  @elseif ($order->isFailedLike())
    <div class="alert alert--error">
      {{ $order->publicMessage() }}
    </div>
  @endif

  <dl class="kv">
    @if ($order->hasFee())
      <dt>Bill amount</dt><dd>LKR {{ number_format($order->amount, 2) }}</dd>
      <dt>Service fee</dt><dd>LKR {{ number_format($order->feeAmount(), 2) }}</dd>
      <dt>Total paid</dt><dd><b>LKR {{ number_format($order->totalPaid(), 2) }}</b></dd>
    @else
      <dt>Cashback earned</dt><dd>LKR {{ number_format($order->profit, 2) }} @if($order->cashback && $order->cashback->status === 'credited')<span class="pill pill--success">Credited</span>@endif</dd>
    @endif
    <dt>Notify number</dt><dd>{{ $order->notify_number ?? '—' }}</dd>
    <dt>Completed at</dt><dd>{{ $order->completed_at?->format('Y-m-d H:i:s') ?? 'Pending…' }}</dd>
  </dl>

  @if ($order->status === 'success' && !empty($hasInvoice))
    <div style="margin-top:22px; text-align:center;">
      <div style="display:inline-block; max-width:520px; border:1px solid var(--line); border-radius:14px; overflow:hidden; box-shadow:var(--shadow-sm);">
        <a href="{{ route('recharge.invoice', $order) }}">
          <img src="{{ route('recharge.invoice.file', $order) }}?v={{ $order->updated_at->timestamp }}"
               alt="Receipt" style="width:100%; display:block;">
        </a>
      </div>
    </div>
  @endif

  <div style="margin-top:22px; display:flex; gap:10px; flex-wrap:wrap;">
    @if ($order->status === 'success')
      <a href="{{ route('recharge.invoice', $order) }}" class="btn-admin btn-admin--ghost btn-admin--sm">
        <x-icon name="bill" :size="13"/> View Receipt
      </a>
      @if (!empty($hasInvoice))
        <a href="{{ route('recharge.invoice.download', $order) }}" data-download class="btn-admin btn-admin--gold">
          <x-icon name="download" :size="17"/> Download Receipt (PNG)
        </a>
      @endif
    @endif
    <a href="{{ route('dashboard') }}" class="btn-admin btn-admin--primary">Go to Dashboard</a>
    <a href="{{ route('dashboard.plans') }}" class="btn-admin btn-admin--ghost">
      <x-icon name="bolt-nav" :size="17"/> New Recharge
    </a>
  </div>
</div>

@if(!auth()->check())
  </div></section>
@endif

@endsection
