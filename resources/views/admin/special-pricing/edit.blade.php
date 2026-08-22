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

  <form method="POST" action="{{ route('admin.special-pricing.bulk', $user) }}" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding:12px 0 18px; border-bottom:1px solid var(--line); margin-bottom:18px;">
    @csrf
    <strong style="font-size:13px; color:var(--navy-800);">Apply to every service:</strong>
    <select name="profit_type" style="height:36px; border-radius:9px; border:1.6px solid rgba(11,42,91,.16); padding:0 10px;">
      <option value="FLAT">LKR (flat)</option>
      <option value="PCT">% (percent)</option>
    </select>
    <input type="number" step="0.01" min="0" name="profit" required placeholder="Profit / commission"
           style="height:36px; border-radius:9px; border:1.6px solid rgba(11,42,91,.16); padding:0 10px; width:140px;">
    <button class="btn-admin btn-admin--primary btn-admin--sm" type="submit" data-loading="Applying…">Apply to all</button>
  </form>

  <form method="POST" action="{{ route('admin.special-pricing.update', $user) }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="mark_retailer" value="1">

    @foreach ($categories as $cat)
      @continue($cat->services->isEmpty())
      <h4 style="margin:18px 0 10px; font-size:13px; letter-spacing:.08em; text-transform:uppercase; color:var(--muted);">{{ $cat->name }}</h4>
      <div class="table-wrap" style="margin-bottom:8px;">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width:36px;">On</th>
              <th>Service</th>
              <th>Default</th>
              <th>Special type</th>
              <th>Special profit</th>
            </tr>
          </thead>
          <tbody>
          @foreach ($cat->services as $s)
            @php $ov = $overrides->get($s->id); @endphp
            <tr>
              <td>
                <input type="checkbox" name="rows[{{ $s->id }}][enabled]" value="1" {{ $ov ? 'checked' : '' }}>
              </td>
              <td>
                <div style="display:flex; align-items:center; gap:10px;">
                  <img src="{{ $s->logoUrl }}" alt="" style="width:28px; height:28px; object-fit:contain;">
                  <div>
                    <b>{{ $s->name }}</b><br>
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
                <select name="rows[{{ $s->id }}][profit_type]" style="height:36px; border-radius:9px; border:1.6px solid rgba(11,42,91,.16); padding:0 8px;">
                  <option value="FLAT" {{ ($ov->profit_type ?? 'FLAT') === 'FLAT' ? 'selected' : '' }}>LKR</option>
                  <option value="PCT" {{ ($ov->profit_type ?? '') === 'PCT' ? 'selected' : '' }}>%</option>
                </select>
              </td>
              <td>
                <input type="number" step="0.01" min="0" name="rows[{{ $s->id }}][profit]"
                       value="{{ $ov->profit ?? $s->profit }}"
                       style="height:36px; border-radius:9px; border:1.6px solid rgba(11,42,91,.16); padding:0 10px; width:120px;">
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
  Tick <b>On</b> to override the default service profit for this customer only. Unticked rows keep the catalog default.
  This customer is marked as a retailer when you save.
</p>

@endsection
