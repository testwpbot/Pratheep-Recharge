@extends('layouts.admin')
@section('title', 'Orders')

@section('content')

<div class="toolbar">
  <form method="GET" action="{{ route('admin.orders.index') }}" style="display:flex; gap:8px; flex-wrap:wrap;">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Ref, customer, phone, txn id…" style="min-width:260px;">
    <select name="status">
      <option value="">All statuses</option>
      <option value="pending"    {{ request('status')==='pending'    ? 'selected':'' }}>Pending</option>
      <option value="processing" {{ request('status')==='processing' ? 'selected':'' }}>Processing</option>
      <option value="success"    {{ request('status')==='success'    ? 'selected':'' }}>Success</option>
      <option value="failed"     {{ request('status')==='failed'     ? 'selected':'' }}>Failed</option>
      <option value="refunded"   {{ request('status')==='refunded'   ? 'selected':'' }}>Refunded</option>
    </select>
    <button class="btn-admin btn-admin--primary" type="submit">Filter</button>
    <a href="{{ route('admin.orders.index') }}" class="btn-admin btn-admin--ghost">Reset</a>
  </form>
</div>

@include('partials.history-period', ['period' => $period, 'keep' => ['q' => request('q'), 'status' => request('status')]])

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Ref</th><th>Customer</th><th>Service</th><th>Account</th>
          <th>Amount</th><th>Cashback</th><th>Provider Txn</th><th>Status</th><th>Date</th><th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($orders as $o)
        <tr>
          <td><b><a href="{{ route('admin.orders.show', $o) }}" style="color:var(--gold-500);">{{ $o->reference }}</a></b></td>
          <td>{{ $o->user->name }}<br><small style="color:var(--muted)">{{ $o->user->email }}</small></td>
          <td>
            {{ $o->customerServiceName() }}
            <br><small style="color:var(--muted)">{{ $o->provider->name }}
              @if ($o->sendOpCode() && $o->sendOpCode() !== (string) ($o->service->op_code ?? ''))
                · via {{ \App\Support\PreferredRoute::adminLabel($o->service, $o->sendOpCode()) }}
              @endif
            </small>
          </td>
          <td>{{ $o->account_number }}</td>
          <td><b>LKR {{ number_format($o->amount, 2) }}</b></td>
          <td>LKR {{ number_format($o->profit, 2) }}</td>
          <td><code style="font-size:12px;">{{ $o->provider_txn_id ?: '—' }}</code></td>
          <td><span class="pill pill--{{ $o->status }}">{{ $o->statusLabel() }}</span></td>
          <td><small>{{ $o->created_at->format('Y-m-d H:i') }}<br>{{ $o->created_at->diffForHumans() }}</small></td>
          <td class="col-actions">
            <div class="td-actions">
              <a href="{{ route('admin.orders.show', $o) }}" class="btn-admin btn-admin--ghost btn-admin--sm">View</a>
              @if ($o->canManualRefund())
                <button type="button"
                        class="btn-admin btn-admin--danger btn-admin--sm"
                        data-refund-btn
                        data-ref="{{ $o->reference }}"
                        data-customer="{{ $o->user->name }}"
                        data-amount="{{ number_format($o->amount, 2) }}"
                        data-status="{{ $o->statusLabel() }}"
                        data-warning="{{ $o->manualRefundWarning() }}"
                        data-action="{{ route('admin.orders.refund', $o) }}">
                  ↩ Refund
                </button>
              @endif
              @if (in_array($o->status, ['pending','processing']))
                <form method="POST" action="{{ route('admin.orders.sync', $o) }}" data-ajax data-ajax-refresh="1">
                  @csrf
                  <button class="btn-admin btn-admin--gold btn-admin--sm" type="submit" data-loading="Checking…">Check status</button>
                </form>
                @php
                  $_oIsHrc = $o->provider && $o->provider->isHappyRechargeCenter();
                @endphp
                @if ($_oIsHrc)
                  <form method="POST" action="{{ route('admin.orders.failover', $o) }}" data-ajax data-ajax-redirect>
                    @csrf
                    <button class="btn-admin btn-admin--danger btn-admin--sm" type="submit" data-loading="Failing…" title="Fail over to Topup Mart">⚠ Failover</button>
                  </form>
                @endif
                @php
                  $_pair = \App\Support\ServicePairs::partnerFromOrder($o);
                  $_pairLabel = $_pair ? \App\Support\PreferredRoute::adminLabel($_pair) : null;
                @endphp
                @if ($_pair)
                  <form method="POST" action="{{ route('admin.orders.transfer', $o) }}" data-ajax data-ajax-redirect onsubmit="return confirm('Send this pending order through {{ addslashes($_pairLabel) }}? The customer is not charged again.');">
                    @csrf
                    <button class="btn-admin btn-admin--primary btn-admin--sm" type="submit" data-loading="Sending…" title="Send via {{ $_pairLabel }}">Send via {{ $_pairLabel }}</button>
                  </form>
                @endif
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="10" style="text-align:center; padding:30px; color:var(--muted);">{{ $period->emptyMessage('orders') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div style="margin-top:18px;">{{ $orders->links() }}</div>
</div>

{{-- Manual refund confirmation modal (shared for every row) --}}
<div class="rc-modal" id="refundModal" hidden>
  <div class="rc-modal__backdrop" data-refund-close></div>
  <div class="rc-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="refundHead">
    <button class="rc-modal__close" data-refund-close aria-label="Close">✕</button>
    <div class="rc-modal__head">
      <h3 id="refundHead">Refund this order?</h3>
      <small id="refundSub">—</small>
    </div>

    <div class="alert alert--error" id="refundWarning" style="margin-bottom:14px; line-height:1.6;">—</div>

    <div style="color:var(--navy-800); font-weight:600; font-size:14px; line-height:1.6; margin-bottom:14px;">
      <p style="margin:0 0 8px;">This will:</p>
      <ul style="margin:0; padding-left:20px;">
        <li>Put <b id="refundAmount">—</b> back into the customer wallet.</li>
        <li>Mark this order as <b>Refunded</b> and reverse any cashback it earned.</li>
        <li><b>Not</b> reclaim money from the provider — you must reconcile that side yourself.</li>
      </ul>
    </div>

    <form method="POST" id="refundForm" action="" data-ajax data-ajax-refresh="1">
      @csrf
      <div class="field" style="margin-bottom:12px;">
        <label>Reason / admin note (optional)</label>
        <textarea name="note" rows="2" class="hpr-input hpr-input--ta" placeholder="Why you are refunding (saved on the order)…"></textarea>
      </div>
      <label style="display:flex; gap:8px; align-items:flex-start; font-size:13px; font-weight:600; color:var(--navy-800); margin-bottom:16px; cursor:pointer;">
        <input type="checkbox" name="acknowledged" value="1" id="refundAck" style="margin-top:3px;">
        <span>I understand the provider is <b>not</b> refunded automatically and I have checked the provider side.</span>
      </label>
      <div style="display:flex; gap:8px; justify-content:flex-end;">
        <button type="button" class="btn-admin btn-admin--ghost" data-refund-close>Cancel</button>
        <button type="submit" class="btn-admin btn-admin--danger" id="refundSubmit" data-loading="Refunding…" disabled>
          Yes, refund to wallet
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
(function(){
  var modal   = document.getElementById('refundModal');
  if (!modal) return;
  document.body.appendChild(modal);

  var form    = document.getElementById('refundForm');
  var sub     = document.getElementById('refundSub');
  var warn    = document.getElementById('refundWarning');
  var amtEl   = document.getElementById('refundAmount');
  var ack     = document.getElementById('refundAck');
  var submit  = document.getElementById('refundSubmit');

  function open(btn){
    form.setAttribute('action', btn.dataset.action || '');
    sub.textContent = 'Order ' + (btn.dataset.ref || '') + ' · ' + (btn.dataset.customer || '') + ' · Status: ' + (btn.dataset.status || '');
    warn.textContent = btn.dataset.warning || '';
    amtEl.textContent = 'LKR ' + (btn.dataset.amount || '0.00');
    ack.checked = false;
    submit.disabled = true;
    form.querySelector('textarea[name=note]').value = '';
    modal.hidden = false;
  }
  function close(){ modal.hidden = true; }

  document.addEventListener('click', function(e){
    var btn = e.target.closest('[data-refund-btn]');
    if (btn){ e.preventDefault(); open(btn); return; }
    if (e.target.closest('[data-refund-close]')){ close(); }
  });
  ack.addEventListener('change', function(){ submit.disabled = !ack.checked; });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
})();
</script>
@endpush
