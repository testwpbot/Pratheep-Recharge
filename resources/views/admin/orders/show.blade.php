@extends('layouts.admin')
@section('title', "Order {$order->reference}")

@php
  $isHrc = $order->provider && $order->provider->isHappyRechargeCenter();
  $failedOver = is_array($order->provider_response) && !empty($order->provider_response['_failover']);
  $canFailover = $isHrc && in_array($order->status, ['pending','processing']);
  $transferPartner = in_array($order->status, ['pending','processing'])
    ? \App\Support\ServicePairs::partnerFromOrder($order)
    : null;
  $transferLabel = $transferPartner
    ? \App\Support\PreferredRoute::adminLabel($transferPartner)
    : null;
  $sendLabel = \App\Support\PreferredRoute::adminLabel($order->service, $order->sendOpCode());
  $switched = is_array($order->provider_response) && !empty($order->provider_response['_transfer']);
  $waitingFunds = $order->isAwaitingProviderFunds();
  $clockNote = $order->clockNote();
  $cron = \App\Models\Setting::cronStatus();
@endphp

@section('content')

<div class="card">
  <div class="card__head">
    <h3>Order #{{ $order->reference }}</h3>
    <span class="pill pill--{{ $order->status }}">{{ $order->statusLabel() }}</span>
    @if ($failedOver)
      <span class="pill pill--pending" title="Re-sent via Topup Mart after HRC pending">Failed over</span>
    @endif
    @if ($switched)
      <span class="pill pill--processing">Switched route</span>
    @endif
  </div>

  <dl class="kv">
    <dt>Customer</dt><dd>{{ $order->user->name }} ({{ $order->user->email }} — {{ $order->user->phone }})</dd>
    <dt>Service</dt><dd>{{ $order->customerServiceName() }} <small style="color:var(--muted);">(customer)</small></dd>
    <dt>Sending through</dt><dd><b>{{ $sendLabel }}</b> <small style="color:var(--muted);">(op {{ $order->sendOpCode() }})</small></dd>
    <dt>Provider</dt><dd>{{ $order->provider->name }}</dd>
    <dt>Recharge number</dt><dd>{{ $order->account_number }}</dd>
    <dt>Notify number</dt><dd>{{ $order->notify_number ?? '—' }}</dd>
    <dt>Amount</dt><dd><b>LKR {{ number_format($order->amount, 2) }}</b></dd>
    <dt>Cashback</dt><dd>LKR {{ number_format($order->profit, 2) }} @if($order->cashback) <span class="pill pill--success" style="margin-left:6px;">Credited</span> @endif</dd>
    <dt>Provider Txn ID</dt><dd><code>{{ $order->provider_txn_id ?: '—' }}</code></dd>
    <dt>Placed at</dt><dd>{{ $order->created_at->format('Y-m-d H:i:s') }} ({{ $order->created_at->diffForHumans() }})</dd>
    <dt>Processed at</dt><dd>{{ $order->processed_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
    <dt>Completed at</dt><dd>{{ $order->completed_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
    <dt>Exact provider error</dt><dd style="white-space:pre-wrap;">{{ $order->message ?: '—' }}</dd>
  </dl>

  @if ($waitingFunds)
    <div class="alert alert--error" style="margin-top:16px;">
      The provider does not have enough money right now. This order is waiting.
      The clock will send it again on the same route when the provider has money.
      Dialog Prepaid uses that same provider wallet, so the clock will not switch this order automatically.
      The customer only sees “Processing” — they cannot see this error.
    </div>
  @endif

  @if ($clockNote)
    <div class="alert {{ $waitingFunds ? 'alert--error' : 'alert--success' }}" style="margin-top:16px;">
      <b>Automatic Dialog Prepaid:</b> {{ $clockNote }}<br>
      <small>Clock last ran: {{ $cron['label'] }}@if($cron['age_minutes'] !== null) ({{ $cron['age_minutes'] }} min ago)@endif</small>
    </div>
  @endif

  @if ($order->isRefunded())
    <div class="alert alert--success" style="margin-top:16px; margin-bottom:0;">
      This recharge did not go through. LKR {{ number_format((float) $order->amount, 2) }} was put back in the customer wallet.
    </div>
  @endif

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

      @if ($transferPartner)
        <button class="btn-admin btn-admin--primary" type="button" id="transferBtn">
          Send via {{ $transferLabel }}
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
        This will re-send <b>the same order</b> ({{ $order->reference }}) through Topup Mart:
      </p>
      <ul style="margin:0 0 12px; padding-left:20px;">
        <li>Attempt to cancel the pending request at Happy Recharge Center (their public API has no cancel endpoint — this is best-effort).</li>
        <li>Keep the original wallet debit (no extra charge / no refund).</li>
        <li>Submit the same number &amp; amount via Topup Mart and record the result on this order.</li>
      </ul>
      <div class="alert alert--error" style="margin-bottom:14px;">
        ⚠ If Happy Recharge Center later completes the original transaction you may need to reconcile manually.
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

@if ($transferPartner)
<div class="rc-modal" id="transferModal" hidden>
  <div class="rc-modal__backdrop" data-close-transfer></div>
  <div class="rc-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="trHead">
    <button class="rc-modal__close" data-close-transfer aria-label="Close">✕</button>
    <div class="rc-modal__head">
      <h3 id="trHead">Send through {{ $transferLabel }}?</h3>
      <small>Order {{ $order->reference }} · LKR {{ number_format($order->amount, 2) }}</small>
    </div>
    <div style="color:var(--navy-800); font-weight:600; font-size:14px; line-height:1.6;">
      <p style="margin:0 0 10px;">
        This order is still pending on <b>{{ $sendLabel }}</b>.
        Send the <b>same order</b> through <b>{{ $transferLabel }}</b> instead.
      </p>
      <ul style="margin:0 0 12px; padding-left:20px;">
        <li>Customer is not charged again.</li>
        <li>Same number and amount.</li>
        <li>If the first route later succeeds, check it by hand.</li>
      </ul>
    </div>
    <form method="POST" action="{{ route('admin.orders.transfer', $order) }}" id="transferForm" data-ajax>
      @csrf
      <div class="field" style="margin-bottom:14px;">
        <label>Admin note (optional)</label>
        <textarea name="note" rows="2" class="hpr-input hpr-input--ta" placeholder="Why you are switching…"></textarea>
      </div>
      <div style="display:flex; gap:8px; justify-content:flex-end;">
        <button type="button" class="btn-admin btn-admin--ghost" data-close-transfer>Cancel</button>
        <button type="submit" class="btn-admin btn-admin--primary" data-loading="Sending…">
          Yes, send via {{ $transferLabel }}
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
@if ($transferPartner)
<script>
(function(){
  var btn = document.getElementById('transferBtn');
  var modal = document.getElementById('transferModal');
  if (!btn || !modal) return;
  document.body.appendChild(modal);
  function open(){ modal.hidden = false; }
  function close(){ modal.hidden = true; }
  btn.addEventListener('click', open);
  modal.querySelectorAll('[data-close-transfer]').forEach(function(el){
    el.addEventListener('click', close);
  });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
})();
</script>
@endif
@endpush
