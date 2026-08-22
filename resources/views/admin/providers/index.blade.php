@extends('layouts.admin')
@section('title', 'Providers')

@section('content')

<div class="card">
  <div class="card__head">
    <h3>Recharge Providers</h3>
  </div>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Name</th><th>Country</th><th>API URL</th><th>Status</th><th>Services</th><th>Wallet Balance</th><th style="text-align:right">Actions</th></tr>
      </thead>
      <tbody>
      @foreach ($providers as $p)
        @php
          $info = $p->is_active && $p->api_key ? $p->fetchBalanceInfo() : ['balance' => null, 'error' => $p->is_active ? 'No API key' : 'disabled'];
          $bal = $info['balance'];
        @endphp
        <tr data-provider-row="{{ $p->id }}">
          <td><b>{{ $p->name }}</b><br><small style="color:var(--muted)">{{ $p->slug }}</small></td>
          <td>{{ strtoupper($p->country) }}</td>
          <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis;"><code style="font-size:12px;">{{ $p->base_url ?: '—' }}</code></td>
          <td>
            <span class="pill pill--{{ $p->is_active ? 'success' : 'failed' }}" data-status-pill>
              {{ $p->is_active ? 'Active' : 'Disabled' }}
            </span>
          </td>
          <td>{{ $p->services()->where('is_active', true)->count() }} service(s)</td>
          <td data-balance-cell>
            @if (!$p->is_active)
              <em style="color:var(--muted)">— disabled —</em>
            @elseif (!$p->api_key)
              <em style="color:var(--muted)">no API key</em>
            @elseif ($bal === null)
              <span class="pill pill--failed" title="{{ $info['error'] }}">{{ \App\Models\Provider::balanceErrorLabel($info['error']) }}</span>
              @if ($info['error'])
                <br><small style="color:var(--muted); font-weight:600;">{{ \Illuminate\Support\Str::limit($info['error'], 90) }}</small>
              @endif
            @else
              @php $cur = strtoupper($p->country) === 'IN' ? 'INR' : 'LKR'; @endphp
              <b style="color:var(--gold-600); font-size:15px;">{{ $cur }} {{ number_format($bal, 2) }}</b>
            @endif
          </td>
          <td class="col-actions">
            <div class="td-actions">
              <a href="{{ route('admin.providers.edit', $p) }}" class="btn-admin btn-admin--ghost btn-admin--sm">Configure</a>
              <form method="POST" action="{{ route('admin.providers.toggle', $p) }}" data-ajax data-ajax-refresh="1">
                @csrf
                <button class="btn-admin btn-admin--ghost btn-admin--sm" type="submit" data-loading="Updating…">
                  {{ $p->is_active ? 'Disable' : 'Enable' }}
                </button>
              </form>
              <form method="POST" action="{{ route('admin.providers.import', $p) }}" data-ajax>
                @csrf
                <button class="btn-admin btn-admin--gold btn-admin--sm" type="submit" data-loading="Importing…">
                  Import Services
                </button>
              </form>
            </div>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div style="padding:14px 18px; font-size:12px; color:var(--muted); border-top:1px solid var(--line);">
    Balance refreshes every 60 seconds. Happy Recharge Center only works from a <b>whitelisted IP</b> — if you see “IP not whitelisted”, ask them to add this server’s public IP. Toggle & Import save without reloading.
  </div>
</div>

@endsection
