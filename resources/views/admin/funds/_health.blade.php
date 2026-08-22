@php
  $overall = $health['overall'] ?? 'unknown';
  $pay = $health['pay'] ?? [];
  $combined = $health['combined_lkr'] ?? null;
  $userTotal = (float) ($health['user_total'] ?? 0);
  $coverPct = ($combined !== null && $userTotal > 0)
      ? min(100, round(($combined / $userTotal) * 100, 1))
      : (($combined !== null && $userTotal <= 0) ? 100 : null);
@endphp

<div class="fund-kpis">
  <div class="fund-kpi">
    <span>Customer wallets</span>
    <b>LKR {{ number_format($userTotal, 2) }}</b>
    <small>{{ (int) ($health['user_count'] ?? 0) }} wallet{{ ($health['user_count'] ?? 0) === 1 ? '' : 's' }} with a balance</small>
  </div>
  <div class="fund-kpi">
    <span>LKR provider float</span>
    <b>@if($combined === null)—@else LKR {{ number_format($combined, 2) }}@endif</b>
    <small>Same-currency APIs (Topup Mart)</small>
  </div>
  <div class="fund-kpi fund-kpi--{{ $overall }}">
    <span>Coverage</span>
    <b>
      @if($overall === 'low') LOW
      @elseif($overall === 'healthy') COVERED
      @else UNKNOWN
      @endif
    </b>
    <small>
      @if($coverPct !== null)
        {{ $coverPct }}% of customer wallets
      @else
        Waiting on a live LKR balance
      @endif
    </small>
    @if($coverPct !== null)
      <div class="fund-bar" aria-hidden="true">
        <div class="fund-bar__fill fund-bar__fill--{{ $overall }}" style="width:{{ $coverPct }}%"></div>
      </div>
    @endif
  </div>
</div>

@if($pay)
  <div class="fund-pay">
    <div class="fund-pay__icon"><x-icon name="alert" :size="18"/></div>
    <div class="fund-pay__body">
      <strong>Top up the API now</strong>
      @foreach ($pay as $p)
        <p>
          Pay <b>{{ $p['currency'] }} {{ number_format($p['amount'], 2) }}</b> to <b>{{ $p['provider'] }}</b>
          — {{ $p['reason'] }}
        </p>
      @endforeach
    </div>
  </div>
@elseif($overall === 'healthy')
  <div class="fund-ok">
    Provider float is above customer wallets. No top-up needed right now.
  </div>
@endif

<div class="table-wrap" style="margin-top:14px;">
  <table class="data-table fund-table">
    <thead>
      <tr>
        <th>Provider</th>
        <th>API balance</th>
        <th>Must cover</th>
        <th>Shortfall</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
    @foreach ($health['providers'] as $r)
      <tr>
        <td>
          <b>{{ $r['name'] }}</b><br>
          <small style="color:var(--muted);">{{ $r['currency'] }} · {{ strtoupper($r['country']) }}</small>
        </td>
        <td>
          @if($r['balance'] === null)
            <span class="pill pill--failed" title="{{ $r['error'] }}">{{ $r['error_label'] }}</span>
          @else
            <b>{{ $r['currency'] }} {{ number_format($r['balance'], 2) }}</b>
            @if($r['coverage_pct'] !== null)
              <br><small style="color:var(--muted);">{{ $r['coverage_pct'] }}% covered</small>
            @endif
          @endif
        </td>
        <td>
          @if($r['is_lkr'])
            LKR {{ number_format($r['user_total'], 2) }}
            <br><small style="color:var(--muted);">all customer wallets</small>
          @else
            {{ $r['currency'] }} {{ number_format($health['settings']['min_inr'] ?? 500, 2) }}
            <br><small style="color:var(--muted);">DTH minimum (not LKR wallets)</small>
          @endif
        </td>
        <td>
          @if($r['status'] === 'low')
            <b style="color:#a52222;">{{ $r['pay_currency'] }} {{ number_format($r['shortfall'], 2) }}</b>
          @elseif($r['status'] === 'healthy')
            <span style="color:#15733f; font-weight:700;">None</span>
          @else
            <em style="color:var(--muted);">—</em>
          @endif
        </td>
        <td>
          <span class="pill pill--{{ $r['status'] === 'healthy' ? 'success' : ($r['status'] === 'low' ? 'failed' : 'pending') }}">
            {{ ucfirst($r['status']) }}
          </span>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
