@php
  /** @var \App\Support\HistoryPeriod $period */
  $keep = $keep ?? [];
  $formId = 'histPeriod-'.substr(md5(json_encode($keep).$period->period), 0, 8);
  $options = [
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    '7d' => 'Last 7 days',
    '30d' => 'Last 30 days',
    'all' => 'All days',
  ];
  $current = $options[$period->period] ?? $period->label;
@endphp
<form method="GET" action="{{ url()->current() }}" class="hist-period" id="{{ $formId }}">
  @foreach ($keep as $k => $v)
    @if ($v !== null && $v !== '')
      <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endif
  @endforeach
  <span class="hist-period__label">Show</span>
  <div class="hpr-dd hist-period__dd" data-hpr-dd data-auto-submit="{{ $formId }}">
    <input type="hidden" name="period" value="{{ $period->period === 'custom' ? 'today' : $period->period }}">
    <button type="button" class="hpr-dd__btn" aria-haspopup="listbox" aria-expanded="false">
      <span class="hpr-dd__label">{{ $current }}</span>
      <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </button>
    <div class="hpr-dd__menu" hidden>
      @foreach ($options as $id => $label)
        <button type="button" class="hpr-dd__item {{ $period->period === $id ? 'is-active' : '' }}"
                data-value="{{ $id }}" data-label="{{ $label }}">{{ $label }}</button>
      @endforeach
    </div>
  </div>
</form>
