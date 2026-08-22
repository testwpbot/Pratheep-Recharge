@extends('layouts.admin')
@section('title', 'Alerts')

@section('content')

<div class="toolbar">
  <div>
    <h3 style="margin:0; font-size:18px; font-weight:800; color:var(--navy-900);">Dashboard alerts</h3>
    <small style="color:var(--muted); font-weight:600;">These banners show on the customer dashboard only — not the homepage.</small>
  </div>
  <a href="{{ route('admin.alerts.create') }}" class="btn-admin btn-admin--gold">
    <x-icon name="plus" :size="14"/> New alert
  </a>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Alert</th>
          <th>Look</th>
          <th>Who sees it</th>
          <th>When</th>
          <th>Status</th>
          <th class="col-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($alerts as $a)
        <tr>
          <td>
            <div style="display:flex; align-items:center; gap:12px; min-width:0;">
              @if($a->imageUrl())
                <img src="{{ $a->imageUrl() }}" alt="" style="width:52px; height:52px; object-fit:cover; border-radius:10px; border:1px solid var(--line); flex:none; background:#fff;">
              @endif
              <div style="min-width:0;">
                <b>{{ $a->heading }}</b><br>
                <small style="color:var(--muted);">{{ $a->title }}</small>
              </div>
            </div>
          </td>
          <td>{{ $a->themeLabel() }}</td>
          <td>{{ $a->audienceLabel() }}</td>
          <td>
            <small>
              @if($a->starts_at || $a->ends_at)
                {{ $a->starts_at?->timezone('Asia/Colombo')->format('M d, H:i') ?: 'Now' }}
                →
                {{ $a->ends_at?->timezone('Asia/Colombo')->format('M d, H:i') ?: 'No end' }}
              @else
                Always
              @endif
            </small>
          </td>
          <td>
            @php $st = $a->statusLabel(); @endphp
            <span class="pill pill--{{ $st === 'On' ? 'success' : ($st === 'Scheduled' ? 'pending' : 'disabled') }}">{{ $st }}</span>
          </td>
          <td class="col-actions">
            <div class="td-actions">
              <a href="{{ route('admin.alerts.edit', $a) }}" class="btn-admin btn-admin--ghost btn-admin--sm">Edit</a>
              <form method="POST" action="{{ route('admin.alerts.toggle', $a) }}">
                @csrf
                <button type="submit" class="btn-admin btn-admin--sm {{ $a->is_active ? 'btn-admin--ghost' : 'btn-admin--gold' }}">
                  {{ $a->is_active ? 'Turn off' : 'Turn on' }}
                </button>
              </form>
              <form method="POST" action="{{ route('admin.alerts.destroy', $a) }}" onsubmit="return confirm('Remove this alert?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-admin btn-admin--danger btn-admin--sm">Remove</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--muted);">No alerts yet. Make one and it will show on the customer dashboard.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div style="margin-top:18px;">{{ $alerts->links() }}</div>
</div>

@endsection
