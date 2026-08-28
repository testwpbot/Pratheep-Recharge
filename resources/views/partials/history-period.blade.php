@php
  /** @var \App\Support\HistoryPeriod $period */
  $keep = $keep ?? [];
@endphp
<div class="hist-period">
  <div class="hist-period__chips" role="tablist" aria-label="Which days to show">
    <a class="hist-period__chip {{ $period->period === 'today' ? 'is-on' : '' }}" href="{{ $period->chipUrl('today', $keep) }}">Today</a>
    <a class="hist-period__chip {{ $period->period === 'yesterday' ? 'is-on' : '' }}" href="{{ $period->chipUrl('yesterday', $keep) }}">Yesterday</a>
    <a class="hist-period__chip {{ $period->period === '7d' ? 'is-on' : '' }}" href="{{ $period->chipUrl('7d', $keep) }}">Last 7 days</a>
    <a class="hist-period__chip {{ $period->period === '30d' ? 'is-on' : '' }}" href="{{ $period->chipUrl('30d', $keep) }}">Last 30 days</a>
    <a class="hist-period__chip {{ $period->period === 'all' ? 'is-on' : '' }}" href="{{ $period->chipUrl('all', $keep) }}">All days</a>
  </div>
  <form method="GET" class="hist-period__custom" action="{{ url()->current() }}">
    @foreach ($keep as $k => $v)
      @if ($v !== null && $v !== '')
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
      @endif
    @endforeach
    <input type="hidden" name="period" value="custom">
    <input type="date" name="from" class="hpr-input" value="{{ $period->period === 'custom' ? $period->from?->toDateString() : '' }}" aria-label="From date">
    <span class="hist-period__to">to</span>
    <input type="date" name="to" class="hpr-input" value="{{ $period->period === 'custom' ? $period->to?->toDateString() : '' }}" aria-label="To date">
    <button class="btn-admin btn-admin--gold btn-admin--sm" type="submit">Show these dates</button>
  </form>
  <p class="hist-period__hint">Showing <b>{{ strtolower($period->label) }}</b>. Older rows stay saved — pick another day to see them. Wallet balance does not reset.</p>
</div>
