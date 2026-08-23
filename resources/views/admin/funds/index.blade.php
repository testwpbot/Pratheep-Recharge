@extends('layouts.admin')
@section('title', 'Provider Money')

@php
  $statusWord = [
      'healthy' => 'OK',
      'low'     => 'Not enough',
      'unknown' => "Can't check",
  ];
@endphp

@section('content')

<div class="card">
  <div class="card__head">
    <div>
      <h3>Provider Money</h3>
      <small style="color:var(--muted); font-weight:600;">
        The provider wallet should have more money than customers have in their wallets.
      </small>
    </div>
    <form method="POST" action="{{ route('admin.funds.refresh') }}">
      @csrf
      <button class="btn-admin btn-admin--gold" type="submit" data-loading="Checking…">
        <span class="btn-label"><x-icon name="bolt" :size="14"/> Check again</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </form>
  </div>

  @include('admin.funds._health', ['health' => $health])
</div>

<div class="card" style="margin-top:20px;">
  <div class="card__head">
    <h3>All records</h3>
    <form method="GET" action="{{ route('admin.funds.index') }}" class="toolbar" style="margin:0; gap:8px;">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <select name="provider_id" onchange="this.form.submit()" style="height:40px; min-width:200px;">
        <option value="">All providers</option>
        @foreach ($providers as $p)
          <option value="{{ $p->id }}" @selected($providerId === $p->id)>{{ $p->name }}</option>
        @endforeach
      </select>
    </form>
  </div>

  <div class="cmp-tabs" style="padding-top:0; margin-bottom:12px;">
    <a class="cmp-tab {{ $tab==='snapshots' ? 'is-active' : '' }}" href="{{ route('admin.funds.index', array_filter(['tab'=>'snapshots','provider_id'=>$providerId])) }}">Provider balance history</a>
    <a class="cmp-tab {{ $tab==='wallets' ? 'is-active' : '' }}" href="{{ route('admin.funds.index', array_filter(['tab'=>'wallets','provider_id'=>$providerId])) }}">Customer money in / out</a>
    <a class="cmp-tab {{ $tab==='orders' ? 'is-active' : '' }}" href="{{ route('admin.funds.index', array_filter(['tab'=>'orders','provider_id'=>$providerId])) }}">Recharge orders</a>
  </div>

  @if($tab === 'snapshots')
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Provider</th>
            <th>Provider wallet</th>
            <th>Customers had</th>
            <th>Need to add</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($snapshots as $s)
          <tr>
            <td><small>{{ $s->created_at->format('Y-m-d H:i') }}</small></td>
            <td><b>{{ $s->provider->name ?? '—' }}</b></td>
            <td>
              @if($s->balance === null)
                <span class="pill pill--failed" title="{{ $s->error }}">{{ \App\Models\Provider::balanceErrorLabel($s->error) }}</span>
              @else
                {{ $s->currency }} {{ number_format($s->balance, 2) }}
              @endif
            </td>
            <td>LKR {{ number_format($s->user_wallet_total, 2) }}</td>
            <td>
              @if((float) $s->shortfall > 0)
                <b style="color:#a52222;">{{ $s->currency }} {{ number_format($s->shortfall, 2) }}</b>
              @else
                —
              @endif
            </td>
            <td>
              <span class="pill pill--{{ $s->status === 'healthy' ? 'success' : ($s->status === 'low' ? 'failed' : 'pending') }}">{{ $statusWord[$s->status] ?? ucfirst($s->status) }}</span>
              @if($s->alerted) <small style="color:var(--muted);">email sent</small> @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align:center; color:var(--muted); padding:28px;">No history yet. Click Check again.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div style="margin-top:16px;">{{ $snapshots->links() }}</div>
  @elseif($tab === 'wallets')
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Customer</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Before → After</th>
            <th>Note</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($walletTx as $tx)
          @php $u = $tx->wallet?->user; @endphp
          <tr>
            <td><small>{{ $tx->created_at->format('Y-m-d H:i') }}</small></td>
            <td>
              <b>{{ $u->name ?? '—' }}</b><br>
              <small style="color:var(--muted);">{{ $u->email ?? '' }}</small>
            </td>
            <td><span class="pill pill--{{ $tx->typePillClass() }}">{{ $tx->typeLabel() }}</span></td>
            <td>
              <b class="{{ $tx->isCredit() ? 'tx-row__amt--pos' : 'tx-row__amt--neg' }}">
                {{ $tx->isCredit() ? '+' : '−' }} LKR {{ number_format(abs((float) $tx->amount), 2) }}
              </b>
            </td>
            <td>
              <small>LKR {{ number_format((float) ($tx->balance_before ?? 0), 2) }}</small>
              →
              <b>LKR {{ number_format((float) $tx->balance_after, 2) }}</b>
            </td>
            <td><small>{{ $tx->description }}</small></td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align:center; color:var(--muted); padding:28px;">No customer money changes yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div style="margin-top:16px;">{{ $walletTx->links() }}</div>
  @else
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>When</th>
            <th>Ref</th>
            <th>Customer</th>
            <th>Provider</th>
            <th>Service</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($orders as $o)
          <tr>
            <td><small>{{ $o->created_at->format('Y-m-d H:i') }}</small></td>
            <td><a href="{{ route('admin.orders.show', $o) }}" style="color:var(--gold-500); font-weight:700;">{{ $o->reference }}</a></td>
            <td>{{ $o->user->name ?? '—' }}</td>
            <td>{{ $o->provider->name ?? '—' }}</td>
            <td>{{ $o->service->name ?? '—' }}</td>
            <td><b>LKR {{ number_format($o->amount, 2) }}</b></td>
            <td><span class="pill pill--{{ $o->status }}">{{ $o->statusLabel() }}</span></td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align:center; color:var(--muted); padding:28px;">No recharge orders yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div style="margin-top:16px;">{{ $orders->links() }}</div>
  @endif
</div>

<div class="card" style="margin-top:20px;">
  <div class="card__head">
    <h3>Email alerts</h3>
    <small style="color:var(--muted); font-weight:600;">The site checks every minute. If the provider wallet is too low, it emails you. It waits a few hours before sending the same warning again.</small>
  </div>
  <form method="POST" action="{{ route('admin.funds.settings') }}">
    @csrf
    <div class="form-grid">
      <div class="field" style="display:flex; align-items:center; gap:10px; padding-top:22px;">
        <label class="sw">
          <input type="checkbox" name="alerts_enabled" value="1" @checked($settings['alerts_enabled'])>
          <span class="sw__slider"></span>
        </label>
        <span style="font-weight:700; color:var(--navy-800); font-size:13.5px;">Email me when the provider wallet is too low</span>
      </div>
      <div class="field">
        <label>Extra email (optional)</label>
        <input type="email" name="alert_email" value="{{ old('alert_email', $settings['alert_email']) }}" placeholder="Leave empty to use Support Email + admin login emails">
      </div>
      <div class="field">
        <label>Wait this many hours before sending again</label>
        <input type="number" name="cooldown_hours" min="1" max="48" value="{{ old('cooldown_hours', $settings['cooldown_hours']) }}" required>
      </div>
      <div class="field">
        <label>Lowest HRC wallet (INR)</label>
        <input type="number" step="0.01" min="0" name="min_inr" value="{{ old('min_inr', $settings['min_inr']) }}" required>
        <div class="hint">DTH uses Indian rupees. Email if Happy Recharge Center goes below this.</div>
      </div>
      <div class="field">
        <label>INR to LKR rate (optional)</label>
        <input type="number" step="0.0001" min="0" name="inr_to_lkr" value="{{ old('inr_to_lkr', $settings['inr_to_lkr'] ?: '') }}" placeholder="e.g. 3.60">
        <div class="hint">Only to show a LKR number. Leave empty — 1 INR is not 1 LKR.</div>
      </div>
    </div>
    <div style="margin-top:18px; display:flex; justify-content:flex-end;">
      <button type="submit" class="btn-admin btn-admin--gold">
        <span class="btn-label"><x-icon name="check" :size="14"/> Save email settings</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </div>
  </form>
</div>

@endsection
