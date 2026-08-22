@extends('layouts.admin')
@section('title', 'Services & Pricing')

@section('content')

<div class="toolbar">
  <form method="GET" action="{{ route('admin.services.index') }}" style="display:flex; gap:8px; flex-wrap:wrap;">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search service name or op code…" style="min-width:220px;">
    <select name="provider_id">
      <option value="">All providers</option>
      @foreach ($providers as $p)
        <option value="{{ $p->id }}" {{ request('provider_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
      @endforeach
    </select>
    <select name="category_id">
      <option value="">All categories</option>
      @foreach ($categories as $c)
        <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
      @endforeach
    </select>
    <select name="status">
      <option value="active" {{ request('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
      <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
      <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All status</option>
    </select>
    <button class="btn-admin btn-admin--primary" type="submit">Filter</button>
    <a href="{{ route('admin.services.index') }}" class="btn-admin btn-admin--ghost">Reset</a>
  </form>

  <span class="spacer" style="flex:1;"></span>

  @foreach ($providers as $p)
    <form method="POST" action="{{ route('admin.providers.import', $p) }}" data-ajax style="display:inline;">
      @csrf
      <button class="btn-admin btn-admin--gold btn-admin--sm" type="submit" data-loading="Importing…">Import from {{ $p->name }}</button>
    </form>
  @endforeach
</div>

<div class="card">
  <form method="POST" action="{{ route('admin.services.bulk') }}" id="bulkForm">
    @csrf
    <div class="card__head">
      <h3>Services ({{ $services->total() }} total)</h3>
      <div style="display:flex; gap:8px; align-items:center;">
        <select name="profit_type" style="height:36px; border-radius:9px; border:1.6px solid rgba(11,42,91,.16); padding:0 10px;">
          <option value="FLAT">LKR (flat)</option>
          <option value="PCT">% (percent)</option>
        </select>
        <input type="number" step="0.01" min="0" name="profit" placeholder="Profit amount"
               style="height:36px; border-radius:9px; border:1.6px solid rgba(11,42,91,.16); padding:0 10px; width:120px;">
        <button class="btn-admin btn-admin--primary btn-admin--sm" type="submit" data-loading="Applying…">Apply to selected</button>
      </div>
    </div>

    <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:30px;"><input type="checkbox" id="checkAll"></th>
          <th>Logo</th>
          <th>Service</th>
          <th>Op Code</th>
          <th>Category</th>
          <th>Provider</th>
          <th>Profit (cashback to customer)</th>
          <th>Status</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($services as $s)
        <tr>
          <td><input type="checkbox" name="ids[]" value="{{ $s->id }}" class="svc-check"></td>
          <td>
            @if ($s->logoUrl)
              <img src="{{ $s->logoUrl }}" alt="{{ $s->name }}" style="width:36px; height:36px; object-fit:contain; background:#fff; padding:3px; border:1px solid var(--line); border-radius:8px;">
            @else
              <div style="width:36px; height:36px; border-radius:8px; background:var(--navy-800); color:#fff; display:grid; place-items:center; font-weight:800; font-size:13px;">{{ strtoupper(substr($s->name,0,1)) }}</div>
            @endif
          </td>
          <td>
            <b>{{ $s->name }}</b><br>
            <small style="color:var(--muted); text-transform:capitalize;">{{ $s->type }}</small>
          </td>
          <td><code style="font-size:13px;">{{ $s->op_code }}</code></td>
          <td>{{ $s->category?->name ?? '—' }}</td>
          <td>
            <span class="pill pill--{{ $s->provider->is_active ? 'success' : 'failed' }}">{{ $s->provider->name }}</span>
          </td>
          <td>
            @if ($s->profit_type === 'PCT')
              <b>{{ number_format($s->profit, 2) }}%</b>
            @else
              <b>LKR {{ number_format($s->profit, 2) }}</b>
            @endif
            <br><small style="color:var(--muted);">cashback</small>
          </td>
          <td><span class="pill pill--{{ $s->is_active ? 'success' : 'failed' }}">{{ $s->is_active ? 'Active' : 'Inactive' }}</span></td>
          <td class="col-actions">
            <div class="td-actions">
              <a href="{{ route('admin.services.edit', $s) }}" class="btn-admin btn-admin--ghost btn-admin--sm">Edit</a>
              <form method="POST" action="{{ route('admin.services.toggle', $s) }}" data-ajax data-ajax-refresh="1">
                @csrf
                <button class="btn-admin btn-admin--ghost btn-admin--sm" type="submit" data-loading="Updating…">{{ $s->is_active ? 'Disable' : 'Enable' }}</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="9" style="text-align:center; padding:30px; color:var(--muted);">No services yet. Go to <b>Providers</b> and click <b>"Import Services"</b> to load the catalog.</td></tr>
      @endforelse
      </tbody>
    </table>
    </div>
  </form>

  <div style="margin-top:18px;">
    {{ $services->links() }}
  </div>
</div>

<script>
  document.getElementById('checkAll')?.addEventListener('change', function(){
    document.querySelectorAll('.svc-check').forEach(c => c.checked = this.checked);
  });
</script>

@endsection
