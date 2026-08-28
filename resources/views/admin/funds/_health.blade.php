@php
  $overall = $health['overall'] ?? 'unknown';
  $pay = $health['pay'] ?? [];
  $combined = $health['combined_lkr'] ?? null;
  $userTotal = (float) ($health['user_total'] ?? 0);
  $coverPct = ($combined !== null && $userTotal > 0)
      ? min(100, round(($combined / $userTotal) * 100, 1))
      : (($combined !== null && $userTotal <= 0) ? 100 : null);
  $statusWord = [
      'healthy' => 'OK',
      'low'     => 'Not enough',
      'unknown' => "Can't check",
  ];
@endphp

<div class="fund-kpis">
  <div class="fund-kpi">
    <span>Customers have</span>
    <b>LKR {{ number_format($userTotal, 2) }}</b>
    <small>{{ (int) ($health['user_count'] ?? 0) }} customer{{ ($health['user_count'] ?? 0) === 1 ? '' : 's' }} with money in wallet</small>
  </div>
  <div class="fund-kpi">
    <span>Provider has (LKR)</span>
    <b>@if($combined === null)—@else LKR {{ number_format($combined, 2) }}@endif</b>
    <small>Money sitting in Topup Mart</small>
  </div>
  <div class="fund-kpi fund-kpi--{{ $overall }}">
    <span>Is it enough?</span>
    <b>
      @if($overall === 'low') Not enough
      @elseif($overall === 'healthy') Yes, enough
      @else Can't check
      @endif
    </b>
    <small>
      @if($coverPct !== null)
        Provider has {{ $coverPct }}% of what customers have
      @else
        Waiting to read the LKR provider wallet
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
      <strong>Add this money now</strong>
      @foreach ($pay as $p)
        <p>
          Add <b>{{ $p['currency'] }} {{ number_format($p['amount'], 2) }}</b> to <b>{{ $p['provider'] }}</b>
          — {{ $p['reason'] }}
        </p>
      @endforeach
    </div>
  </div>
@elseif($overall === 'healthy')
  <div class="fund-ok">
    Provider has more money than customers. You do not need to add more now.
  </div>
@endif

<div class="table-wrap" style="margin-top:14px;">
  <table class="data-table fund-table">
    <thead>
      <tr>
        <th>Provider</th>
        <th>Provider wallet</th>
        <th>Should have</th>
        <th>Need to add</th>
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
              <br><small style="color:var(--muted);">{{ $r['coverage_pct'] }}% of what it should have</small>
            @endif
          @endif
        </td>
        <td>
          @if($r['is_lkr'])
            LKR {{ number_format($r['user_total'], 2) }}
            <br><small style="color:var(--muted);">same as all customer wallets</small>
          @else
            {{ $r['currency'] }} {{ number_format($health['settings']['min_inr'] ?? 500, 2) }}
            <br><small style="color:var(--muted);">lowest DTH wallet we want (INR, not LKR)</small>
          @endif
        </td>
        <td>
          @if($r['status'] === 'low')
            <b style="color:#a52222;">{{ $r['pay_currency'] }} {{ number_format($r['shortfall'], 2) }}</b>
          @elseif($r['status'] === 'healthy')
            <span style="color:#15733f; font-weight:700;">Nothing</span>
          @else
            <em style="color:var(--muted);">—</em>
          @endif
        </td>
        <td>
          <span class="pill pill--{{ $r['status'] === 'healthy' ? 'success' : ($r['status'] === 'low' ? 'failed' : 'pending') }}">
            {{ $statusWord[$r['status']] ?? $r['status'] }}
          </span>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
