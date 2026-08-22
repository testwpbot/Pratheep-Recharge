@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')

<div class="stats-grid">
  <a href="{{ route('admin.users.index') }}" class="stat" style="text-decoration:none; color:inherit;">
    <b>{{ number_format($stats['users']) }}</b><span>Customers</span>
  </a>
  <div class="stat"><b>{{ number_format($stats['services']) }}</b><span>Active Services</span></div>
  <div class="stat"><b>{{ number_format($stats['orders_today']) }}</b><span>Orders Today</span></div>
  <div class="stat"><b>LKR {{ number_format($stats['revenue'], 2) }}</b><span>Total Processed</span></div>
  <div class="stat"><b>LKR {{ number_format($stats['cashback'], 2) }}</b><span>Cashback Given</span></div>
  <div class="stat"><b>{{ number_format($stats['pending']) }}</b><span>Pending Orders</span></div>
</div>

<div class="card">
  <div class="card__head">
    <div>
      <h3>Provider Money</h3>
      <small style="color:var(--muted); font-weight:600;">Does the provider have more money than customers have in their wallets?</small>
    </div>
    <a href="{{ route('admin.funds.index') }}" class="btn-admin btn-admin--gold btn-admin--sm">See all records</a>
  </div>

  @include('admin.funds._health', ['health' => $health])

  <div class="fund-split" style="margin-top:18px;">
    <div>
      <h4 class="fund-subhead">Latest customer money</h4>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>When</th><th>Customer</th><th>Type</th><th>Amount</th></tr></thead>
          <tbody>
          @forelse ($recentWallet as $tx)
            @php $u = $tx->wallet?->user; @endphp
            <tr>
              <td><small>{{ $tx->created_at->diffForHumans() }}</small></td>
              <td>{{ $u->name ?? '—' }}</td>
              <td><span class="pill pill--{{ $tx->type === 'debit' ? 'failed' : 'success' }}">{{ ucfirst($tx->type) }}</span></td>
              <td><b>{{ $tx->isCredit() ? '+' : '−' }} LKR {{ number_format(abs((float) $tx->amount), 2) }}</b></td>
            </tr>
          @empty
            <tr><td colspan="4" style="text-align:center; color:var(--muted); padding:20px;">No wallet activity yet.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div>
      <h4 class="fund-subhead">Latest provider balances</h4>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>When</th><th>Provider</th><th>Balance</th><th>Status</th></tr></thead>
          <tbody>
          @forelse ($recentSnaps as $s)
            <tr>
              <td><small>{{ $s->created_at->diffForHumans() }}</small></td>
              <td>{{ $s->provider->name ?? '—' }}</td>
              <td>
                @if($s->balance === null)
                  <span class="pill pill--failed">{{ \App\Models\Provider::balanceErrorLabel($s->error) }}</span>
                @else
                  {{ $s->currency }} {{ number_format($s->balance, 2) }}
                @endif
              </td>
              <td><span class="pill pill--{{ $s->status === 'healthy' ? 'success' : ($s->status === 'low' ? 'failed' : 'pending') }}">{{ $s->status === 'healthy' ? 'OK' : ($s->status === 'low' ? 'Not enough' : "Can't check") }}</span></td>
            </tr>
          @empty
            <tr><td colspan="4" style="text-align:center; color:var(--muted); padding:20px;">No history yet — open Provider Money and click Check again.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card" style="margin-top:20px;">
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
        $row = $byId[$p->id] ?? null;
        $bal = $row['balance'] ?? null;
        $err = $row['error'] ?? null;
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
            <span class="pill pill--failed" title="{{ $err }}">{{ \App\Models\Provider::balanceErrorLabel($err) }}</span>
          @else
            @php $cur = $p->currency(); @endphp
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
          <td><span class="pill pill--{{ $o->status }}">{{ $o->statusLabel() }}</span></td>
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
