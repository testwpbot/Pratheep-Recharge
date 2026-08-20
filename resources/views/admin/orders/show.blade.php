@extends('layouts.admin')
@section('title', "Order {$order->reference}")

@php
  $isHrc = $order->provider && (str_contains($order->provider->api_class, 'HappyRechargeCenter') || $order->provider->slug === 'happy-recharge-center');
  $canFailover = $isHrc && in_array($order->status, ['pending','processing']);
@endphp

@section('content')

<div class="card">
  <div class="card__head">
    <h3>Order #{{ $order->reference }}</h3>
    <span class="pill pill--{{ $order->status }}">{{ ucfirst($order->status) }}</span>
  </div>

  <dl class="kv">
    <dt>Customer</dt><dd>{{ $order->user->name }} ({{ $order->user->email }} — {{ $order->user->phone }})</dd>
    <dt>Service</dt><dd>{{ $order->service->name }} <small style="color:var(--muted);">({{ $order->service->op_code }})</small></dd>
    <dt>Provider</dt><dd>{{ $order->provider->name }}</dd>
    <dt>Recharge number</dt><dd>{{ $order->account_number }}</dd>
    <dt>Notify number</dt><dd>{{ $order->notify_number ?? '—' }}</dd>
    <dt>Amount</dt><dd><b>LKR {{ number_format($order->amount, 2) }}</b></dd>
    <dt>Cashback</dt><dd>LKR {{ number_format($order->profit, 2) }} @if($order->cashback) <span class="pill pill--success" style="margin-left:6px;">Credited</span> @endif</dd>
    <dt>Provider Txn ID</dt><dd><code>{{ $order->provider_txn_id ?: '—' }}</code></dd>
    <dt>Placed at</dt><dd>{{ $order->created_at->format('Y-m-d H:i:s') }} ({{ $order->created_at->diffForHumans() }})</dd>
    <dt>Processed at</dt><dd>{{ $order->processed_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
    <dt>Completed at</dt><dd>{{ $order->completed_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
    <dt>Message</dt><dd style="white-space:pre-wrap;">{{ $order->message ?: '—' }}</dd>
  </dl>

  @if (in_array($order->status, ['pending','processing']))
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:18px;">
      <form method="POST" action="{{ route('admin.orders.sync', $order) }}" data-ajax data-ajax-refresh="1">
        @csrf
        <button class="btn-admin btn-admin--gold" type="submit" data-loading="Checking…">Re-check provider status</button>
      </form>

      @if ($canFailover)
        <button class="btn-admin btn-admin--danger" type="button" id="failoverBtn">
          ⚠ Fail over to Topup Mart
        </button>
      @endif
    </div>
  @endif
</div>

@if ($order->provider_response)
<div class="card">
  <div class="card__head"><h3>Raw Provider Response</h3></div>
  <pre style="background:#f7f9fd; padding:16px; border-radius:12px; font-size:12.5px; overflow:auto;">{{ json_encode($order->provider_response, JSON_PRETTY_PRINT) }}</pre>
</div>
@endif

<a href="{{ route('admin.orders.index') }}" class="btn-admin btn-admin--ghost" style="margin-top:18px; display:inline-flex;">← Back to orders</a>

@if ($canFailover)
{{-- Failover confirmation modal (rendered outside any transformed ancestor) --}}
<div class="rc-modal" id="failoverModal" hidden>
  <div class="rc-modal__backdrop" data-close></div>
  <div class="rc-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="foHead">
    <button class="rc-modal__close" data-close aria-label="Close">✕</button>
    <div class="rc-modal__head">
      <h3 id="foHead">Fail over to Topup Mart?</h3>
      <small>Order {{ $order->reference }} · LKR {{ number_format($order->amount, 2) }}</small>
    </div>
    <div style="color:var(--navy-800); font-weight:600; font-size:14px; line-height:1.6;">
      <p style="margin:0 0 10px;">
        This will:
      </p>
      <ul style="margin:0 0 12px; padding-left:20px;">
        <li>Mark this Happy Recharge Center order as <b>failed</b> and auto-refund the wallet (LKR <b>{{ number_format($order->amount, 2) }}</b>).</li>
        <li>Create a <b>new order</b> through Topup Mart for the same number & amount (debits wallet again).</li>
        <li>Add notes to both orders referencing each other.</li>
      </ul>
      <div class="alert alert--error" style="margin-bottom:14px;">
        ⚠ Happy Recharge Center has <b>no cancel API</b>. If they later complete this transaction you may need to reconcile manually.
      </div>
    </div>
    <form method="POST" action="{{ route('admin.orders.failover', $order) }}" id="failoverForm" data-ajax>
      @csrf
      <div class="field" style="margin-bottom:14px;">
        <label>Admin note (optional)</label>
        <textarea name="note" rows="2" class="hpr-input hpr-input--ta" placeholder="Reason for failover (added to order message)…"></textarea>
      </div>
      <div style="display:flex; gap:8px; justify-content:flex-end;">
        <button type="button" class="btn-admin btn-admin--ghost" data-close>Cancel</button>
        <button type="submit" class="btn-admin btn-admin--danger" data-loading="Failing over…">
          Yes, fail over
        </button>
      </div>
    </form>
  </div>
</div>
@endif

@endsection

@push('scripts')
@if ($canFailover)
<script>
(function(){
  var btn = document.getElementById('failoverBtn');
  var modal = document.getElementById('failoverModal');
  if (!btn || !modal) return;
  // Move modal to body so it's outside any transformed ancestor (safe for fixed positioning).
  document.body.appendChild(modal);
  function open(){ modal.hidden = false; }
  function close(){ modal.hidden = true; }
  btn.addEventListener('click', open);
  modal.querySelectorAll('[data-close]').forEach(function(el){
    el.addEventListener('click', close);
  });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
})();
</script>
@endif
@endpush
