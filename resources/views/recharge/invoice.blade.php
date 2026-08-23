@extends('layouts.dashboard')
@section('title', 'Receipt ' . $order->reference)

@section('content')
<div class="card" style="max-width:760px; margin:0 auto;">
  <div class="card__head" style="flex-wrap:wrap;">
    <div>
      <h3 style="margin:0;">Receipt</h3>
      <span style="color:var(--muted); font-size:13px; font-weight:700;">{{ $order->reference }}</span>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      @if ($invoiceUrl)
        <a href="{{ route('recharge.invoice.download', $order) }}" data-download class="btn-admin btn-admin--gold btn-admin--sm">
          <x-icon name="download" :size="12"/> Download PNG
        </a>
      @endif
      <a href="{{ route('recharge.show', $order) }}" class="btn-admin btn-admin--ghost btn-admin--sm">
        <x-icon name="bill" :size="12"/> Order Details
      </a>
      <a href="{{ route('dashboard.plans') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
        <x-icon name="bolt-nav" :size="12"/> New Recharge
      </a>
    </div>
  </div>

  @if ($invoiceUrl)
    <div style="text-align:center; padding:10px 10px 0;">
      <img src="{{ $invoiceUrl }}?v={{ $order->updated_at->timestamp }}"
           alt="Receipt {{ $order->reference }}"
           style="max-width:100%; width:680px; height:auto; border-radius:14px; border:1px solid var(--line); box-shadow:var(--shadow-sm);">
    </div>
    <p style="text-align:center; color:var(--muted); font-size:12px; font-weight:600; margin:12px 0 4px;">
      Tap <b>Download PNG</b> above to save a full-resolution copy.
    </p>
  @elseif ($order->status === 'success')
    <div class="hpr-receipt-fallback">
      <p style="font-weight:800; color:var(--navy-900); margin:0 0 12px;">Receipt</p>
      <dl class="kv">
        <dt>Reference</dt><dd>{{ $order->reference }}</dd>
        <dt>Service</dt><dd>{{ $order->service->name ?? '—' }}</dd>
        <dt>Account</dt><dd>{{ $order->account_number }}</dd>
        <dt>Amount</dt><dd>LKR {{ number_format((float) $order->amount, 2) }}</dd>
        @if ((float) $order->profit > 0)
          <dt>Cashback</dt><dd>LKR {{ number_format((float) $order->profit, 2) }}</dd>
        @endif
        <dt>Status</dt><dd>{{ $order->statusLabel() }}</dd>
        <dt>Date</dt><dd>{{ ($order->completed_at ?: $order->created_at)->timezone('Asia/Colombo')->format('d M Y, h:i A') }}</dd>
      </dl>
      <p style="margin:16px 0 0; font-size:13px; color:var(--muted); font-weight:600;">
        Your order is successful. The picture receipt could not be drawn on the server yet. This text copy is still valid.
      </p>
    </div>
  @elseif ($order->isFailedLike())
    <div style="text-align:center; padding:48px 20px; color:var(--muted);">
      <p style="font-weight:700; color:var(--navy-800); margin:0 0 4px;">No receipt for this order</p>
      <p style="margin:0; font-size:13px;">This recharge did not complete, so there is no receipt to download.</p>
      <p style="margin-top:16px;">
        <a href="{{ route('recharge.show', $order) }}" class="btn-admin btn-admin--primary btn-admin--sm">View order</a>
      </p>
    </div>
  @else
    <div style="text-align:center; padding:60px 20px; color:var(--muted);">
      <div style="width:64px; height:64px; margin:0 auto 14px; border-radius:50%; background:#f7f9fd; display:grid; place-items:center; animation:pulse 1.6s ease-in-out infinite;">
        <x-icon name="bill" :size="28"/>
      </div>
      <p style="font-weight:700; color:var(--navy-800); margin:0 0 4px;">Receipt not ready yet</p>
      <p style="margin:0; font-size:13px;">Your order is still being processed. The receipt will appear here automatically once payment is confirmed.</p>
      <p style="margin-top:16px;">
        <a href="{{ route('recharge.show', $order) }}" class="btn-admin btn-admin--primary btn-admin--sm">Track Order Status</a>
      </p>
    </div>
  @endif
</div>
@endsection

@push('styles')
<style>
.hpr-receipt-fallback{padding:8px 6px 4px;}
</style>
@endpush
