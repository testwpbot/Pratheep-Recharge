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
      <a href="{{ route('recharge.invoice.download', $order) }}" data-download class="btn-admin btn-admin--gold btn-admin--sm">
        <x-icon name="download" :size="12"/> Download PNG
      </a>
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
