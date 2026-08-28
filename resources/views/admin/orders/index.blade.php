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

@endsection
