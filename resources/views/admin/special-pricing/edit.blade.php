@extends('layouts.admin')
@section('title', "Special pricing — {$user->name}")

@section('content')

<div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
  <a href="{{ route('admin.special-pricing.index') }}" class="btn-admin btn-admin--ghost btn-admin--sm">← All customers</a>
</div>

<div class="card">
  <div class="card__head">
    <div>
      <h3>{{ $user->name }}</h3>
      <small style="color:var(--muted); font-weight:600;">{{ $user->email }} · {{ $user->phone }}
        @if($user->is_retailer) · <span class="pill pill--processing">Retailer</span>@endif
      </small>
    </div>
  </div>

  <form method="POST" action="{{ route('admin.special-pricing.bulk', $user) }}" class="sp-bulk">
    @csrf
    <strong style="font-size:13px; color:var(--navy-800);">Apply to every service</strong>
    <div class="hpr-dd" data-hpr-dd>
      <input type="hidden" name="profit_type" value="FLAT">
      <button type="button" class="hpr-dd__btn">
        <span class="hpr-dd__label">LKR (flat)</span>
        <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </button>
      <div class="hpr-dd__menu" hidden>
        <button type="button" class="hpr-dd__item is-active" data-value="FLAT" data-label="LKR (flat)">LKR (flat)</button>
        <button type="button" class="hpr-dd__item" data-value="PCT" data-label="% (percent)">% (percent)</button>
      </div>
    </div>
    <input class="sp-amt" type="number" step="0.01" name="profit" required placeholder="Profit / commission">
    <button class="btn-admin btn-admin--primary btn-admin--sm" type="submit" data-loading="Applying…">Apply to all</button>
  </form>
  <p class="hint" style="margin:8px 2px 0; color:var(--muted); font-size:12.5px;">
    <b>Positive</b> = cashback for this customer. <b>Negative</b> = a customer fee (surcharge) added on top —
    only applied to bill-type services (utility, postpaid, insurance, wallet).
  </p>
  @error('profit')<p class="hint" style="margin:6px 2px 0; color:var(--danger,#c0392b); font-weight:700;">{{ $message }}</p>@enderror
  @error('rows')<p class="hint" style="margin:6px 2px 0; color:var(--danger,#c0392b); font-weight:700;">{{ $message }}</p>@enderror

  <form method="POST" action="{{ route('admin.special-pricing.update', $user) }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="mark_retailer" value="1">

    @foreach ($categories as $cat)
      @continue($cat->services->isEmpty())
      <h4 style="margin:18px 0 10px; font-size:13px; letter-spacing:.08em; text-transform:uppercase; color:var(--muted);">{{ $cat->name }}</h4>
      <div class="table-wrap" style="margin-bottom:8px;">
        <table class="data-table sp-table sp-table--svc">
          <colgroup>
            <col class="c-on"><col class="c-svc"><col class="c-def"><col class="c-type"><col class="c-amt">
          </colgroup>
          <thead>
            <tr>
              <th>On</th>
              <th>Service</th>
              <th>Default</th>
              <th>Special type</th>
              <th>Special profit</th>
            </tr>
          </thead>
          <tbody>
          @foreach ($cat->services as $s)
            @php
              $ov = $overrides->get($s->id);
              $type = $ov->profit_type ?? 'FLAT';
              $allowsFee = $s->allowsFee();
            @endphp
            <tr data-allows-fee="{{ $allowsFee ? '1' : '0' }}">
              <td>
                <label class="sw">
                  <input type="checkbox" name="rows[{{ $s->id }}][enabled]" value="1" {{ $ov ? 'checked' : '' }}>
                  <span class="sw__slider"></span>
                </label>
              </td>
              <td>
                <div class="svc-cell">
                  <img src="{{ $s->logoUrl }}" alt="">
                  <div>
                    <b>{{ $s->name }}</b>
                    <small style="color:var(--muted);">{{ $s->provider->name ?? '' }} · {{ $s->op_code }}</small>
                  </div>
                </div>
              </td>
              <td>
                @if ($s->profit_type === 'PCT')
                  {{ number_format($s->profit, 2) }}%
                @else
                  LKR {{ number_format($s->profit, 2) }}
                @endif
              </td>
              <td>
                <div class="hpr-dd hpr-dd--sm" data-hpr-dd>
                  <input type="hidden" name="rows[{{ $s->id }}][profit_type]" value="{{ $type }}">
                  <button type="button" class="hpr-dd__btn">
                    <span class="hpr-dd__label">{{ $type === 'PCT' ? '%' : 'LKR' }}</span>
                    <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </button>
                  <div class="hpr-dd__menu" hidden>
                    <button type="button" class="hpr-dd__item {{ $type==='FLAT' ? 'is-active' : '' }}" data-value="FLAT" data-label="LKR">LKR</button>
                    <button type="button" class="hpr-dd__item {{ $type==='PCT' ? 'is-active' : '' }}" data-value="PCT" data-label="%">%</button>
                  </div>
                </div>
              </td>
              <td>
                <input class="sp-amt" type="number" step="0.01" name="rows[{{ $s->id }}][profit]"
                       value="{{ $ov->profit ?? $s->profit }}"
                       @unless($allowsFee) min="0" title="This service is not a bill-type service, so it cannot have a customer fee (negative value)." @endunless>
                <span class="sp-fee-tag" hidden>fee</span>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endforeach

    <div style="margin-top:22px; display:flex; gap:10px; flex-wrap:wrap;">
      <button type="submit" class="btn-admin btn-admin--gold" data-loading="Saving…">Save special pricing</button>
      <a href="{{ route('admin.special-pricing.index') }}" class="btn-admin btn-admin--ghost">Cancel</a>
    </div>
  </form>

  <form method="POST" action="{{ route('admin.special-pricing.clear', $user) }}" style="margin-top:14px;"
        onsubmit="return confirm('Clear all special prices for this customer?');">
    @csrf
    <button type="submit" class="btn-admin btn-admin--danger btn-admin--sm">Clear all overrides</button>
  </form>
</div>

<p style="margin-top:14px; color:var(--muted); font-size:13px;">
  Turn <b>On</b> to override the default service profit for this customer only. Off rows keep the catalog default.
  A <b>positive</b> value is cashback for this customer; a <b>negative</b> value is a customer fee (surcharge) that is
  added on top of the bill — allowed only on bill-type services (utility, postpaid, insurance, wallet).
  This customer is marked as a retailer when you save.
</p>

<style>
  .sp-fee-tag{
    display:inline-block; margin-left:6px; padding:1px 7px; border-radius:999px;
    background:#fff4d6; color:#a67c00; font-size:11px; font-weight:800; letter-spacing:.04em;
    text-transform:uppercase; vertical-align:middle;
  }
  tr.sp-row--fee .sp-amt{ border-color:#e0a800; background:#fffdf5; }
</style>
<script>
(function(){
  function refreshRow(input){
    var tr = input.closest('tr');
    if (!tr) return;
    var tag = tr.querySelector('.sp-fee-tag');
    var val = parseFloat(input.value || '0');
    var isFee = val < 0;
    tr.classList.toggle('sp-row--fee', isFee);
    if (tag) tag.hidden = !isFee;
  }
  document.querySelectorAll('.sp-table--svc .sp-amt').forEach(function(input){
    refreshRow(input);
    input.addEventListener('input', function(){ refreshRow(input); });
  });
})();
</script>

@endsection
