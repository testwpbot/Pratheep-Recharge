@extends('layouts.admin')
@section('title', 'Special Pricing')

@section('content')

<div class="toolbar">
  <form method="GET" action="{{ route('admin.special-pricing.index') }}" id="spFilterForm" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; width:100%;">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, email, phone…" style="min-width:260px; flex:1;">

    <div class="hpr-dd" data-hpr-dd data-auto-submit="spFilterForm">
      <input type="hidden" name="filter" value="{{ $filter }}">
      <button type="button" class="hpr-dd__btn">
        <span class="hpr-dd__label">
          @if($filter==='retailers') Retailers only
          @elseif($filter==='special') Has special pricing
          @else All customers
          @endif
        </span>
        <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </button>
      <div class="hpr-dd__menu" hidden>
        <button type="button" class="hpr-dd__item {{ $filter==='all' ? 'is-active' : '' }}" data-value="all" data-label="All customers">All customers</button>
        <button type="button" class="hpr-dd__item {{ $filter==='retailers' ? 'is-active' : '' }}" data-value="retailers" data-label="Retailers only">Retailers only</button>
        <button type="button" class="hpr-dd__item {{ $filter==='special' ? 'is-active' : '' }}" data-value="special" data-label="Has special pricing">Has special pricing</button>
      </div>
    </div>

    <button class="btn-admin btn-admin--primary" type="submit">Filter</button>
    <a href="{{ route('admin.special-pricing.index') }}" class="btn-admin btn-admin--ghost">Reset</a>
  </form>
</div>

<div class="card">
  <div class="card__head">
    <h3>Special Pricing</h3>
    <small style="color:var(--muted); font-weight:600;">Pick a retailer and set their commission on every service. They will see that cashback on dashboard, plans, and checkout.</small>
  </div>

  <div class="table-wrap">
    <table class="data-table sp-table sp-table--users">
      <colgroup>
        <col class="c-user"><col class="c-phone"><col class="c-role"><col class="c-count"><col class="c-act">
      </colgroup>
      <thead>
        <tr>
          <th>Customer</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Special services</th>
          <th class="col-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($users as $u)
        <tr>
          <td>
            <b>{{ $u->name }}</b><br>
            <small style="color:var(--muted)">{{ $u->email }}</small>
          </td>
          <td>{{ $u->phone }}</td>
          <td>
            @if ($u->is_retailer)
              <span class="pill pill--processing">Retailer</span>
            @else
              <span class="pill pill--disabled">Customer</span>
            @endif
          </td>
          <td>
            @if ($u->special_prices_count)
              <b>{{ $u->special_prices_count }}</b> <small style="color:var(--muted)">override(s)</small>
            @else
              <em style="color:var(--muted)">default pricing</em>
            @endif
          </td>
          <td class="col-actions">
            <div class="td-actions">
              <a href="{{ route('admin.special-pricing.edit', $u) }}" class="btn-admin btn-admin--gold btn-admin--sm">Set pricing</a>
              <form method="POST" action="{{ route('admin.special-pricing.retailer', $u) }}" data-ajax data-ajax-refresh="1">
                @csrf
                <button class="btn-admin btn-admin--ghost btn-admin--sm" type="submit" data-loading="Saving…">
                  {{ $u->is_retailer ? 'Unmark retailer' : 'Mark retailer' }}
                </button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--muted);">No customers match this filter.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div style="margin-top:18px;">{{ $users->links() }}</div>
</div>

@endsection
