@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')

<div class="stats-grid">
  <div class="stat"><b>{{ number_format($stats['users']) }}</b><span>Customers</span></div>
  <div class="stat"><b>{{ number_format($stats['services']) }}</b><span>Active Services</span></div>
  <div class="stat"><b>{{ number_format($stats['orders_today']) }}</b><span>Orders Today</span></div>
  <div class="stat"><b>LKR {{ number_format($stats['revenue'], 2) }}</b><span>Total Processed</span></div>
  <div class="stat"><b>LKR {{ number_format($stats['cashback'], 2) }}</b><span>Cashback Given</span></div>
  <div class="stat"><b>{{ number_format($stats['pending']) }}</b><span>Pending Orders</span></div>
</div>

<div class="card">
  <div class="card__head">
    <h3>Providers</h3>
    <a href="{{ route('admin.providers.index') }}" class="btn-admin btn-admin--ghost btn-admin--sm">Manage providers</a>
  </div>

  <div class="table-wrap">
  <table class="data-table">
    <thead><tr><th>Provider</th><th>Country</th><th>Status</th><th>Wallet Balance</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach ($providers as $p)
      @php
        $info = $p->is_active && $p->api_key ? $p->fetchBalanceInfo() : ['balance' => null, 'error' => null];
        $bal = $info['balance'];
      @endphp
      <tr>
        <td><b>{{ $p->name }}</b><br><small style="color:var(--muted)">{{ $p->base_url ?: '(no URL configured)' }}</small></td>
        <td>{{ strtoupper($p->country) }}</td>
        <td>
          <span class="pill pill--{{ $p->is_active ? 'success' : 'failed' }}">{{ $p->is_active ? 'Active' : 'Disabled' }}</span>
        </td>
        <td>
          @if (!$p->is_active)
            <em style="color:var(--muted)">— disabled —</em>
          @elseif (!$p->api_key)
            <em style="color:var(--muted)">no API key</em>
          @elseif ($bal === null)
            <span class="pill pill--failed" title="{{ $info['error'] }}">{{ \App\Models\Provider::balanceErrorLabel($info['error']) }}</span>
          @else
            @php $cur = strtoupper($p->country) === 'IN' ? 'INR' : 'LKR'; @endphp
            <b style="color:var(--gold-600); font-size:15px;">{{ $cur }} {{ number_format($bal, 2) }}</b>
          @endif
        </td>
        <td class="col-actions">
          <div class="td-actions">
            <a href="{{ route('admin.providers.edit', $p) }}" class="btn-admin btn-admin--ghost btn-admin--sm">Configure</a>
            <form method="POST" action="{{ route('admin.providers.import', $p) }}">
              @csrf
              <button class="btn-admin btn-admin--primary btn-admin--sm" type="submit">Import Services</button>
            </form>
          </div>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
  </div>
</div>

<div class="card" style="margin-top:20px;">
  <div class="card__head">
    <h3>Recent Orders</h3>
    <a href="{{ route('admin.orders.index') }}" class="btn-admin btn-admin--ghost btn-admin--sm">View all orders</a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Ref</th><th>Customer</th><th>Service</th><th>Amount</th><th>Cashback</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      @forelse ($recentOrders as $o)
        <tr>
          <td><a href="{{ route('admin.orders.show', $o) }}" style="color:var(--gold-500); font-weight:700;">{{ $o->reference }}</a></td>
          <td>{{ $o->user->name }}<br><small style="color:var(--muted)">{{ $o->user->email }}</small></td>
          <td>{{ $o->service->name }}<br><small style="color:var(--muted)">{{ $o->account_number }}</small></td>
          <td><b>LKR {{ number_format($o->amount, 2) }}</b></td>
          <td>LKR {{ number_format($o->profit, 2) }}</td>
          <td><span class="pill pill--{{ $o->status }}">{{ ucfirst($o->status) }}</span></td>
          <td><small>{{ $o->created_at->diffForHumans() }}</small></td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center; color:var(--muted); padding:24px;">No orders yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
