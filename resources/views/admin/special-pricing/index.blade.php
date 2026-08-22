@extends('layouts.admin')
@section('title', 'Special Pricing')

@section('content')

<div class="toolbar">
  <form method="GET" action="{{ route('admin.special-pricing.index') }}" style="display:flex; gap:8px; flex-wrap:wrap;">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, email, phone…" style="min-width:260px;">
    <select name="filter">
      <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All customers</option>
      <option value="retailers" {{ $filter === 'retailers' ? 'selected' : '' }}>Retailers only</option>
      <option value="special" {{ $filter === 'special' ? 'selected' : '' }}>Has special pricing</option>
    </select>
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
    <table class="data-table">
      <thead>
        <tr>
          <th>Customer</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Special services</th>
          <th style="text-align:right">Actions</th>
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
