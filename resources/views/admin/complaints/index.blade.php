@extends('layouts.admin')
@section('title', 'Complaints')

@section('content')

<div class="toolbar">
  <form method="GET" action="{{ route('admin.complaints.index') }}" style="display:flex; gap:8px; flex-wrap:wrap;">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Ref, customer, order, mobile, subject…" style="min-width:260px;">
    <select name="status">
      <option value="all"        {{ $status==='all'        ? 'selected':'' }}>All statuses ({{ $counts['all'] }})</option>
      <option value="open"       {{ $status==='open'       ? 'selected':'' }}>Open ({{ $counts['open'] }})</option>
      <option value="in_progress"{{ $status==='in_progress'? 'selected':'' }}>In Progress ({{ $counts['in_progress'] }})</option>
      <option value="resolved"   {{ $status==='resolved'   ? 'selected':'' }}>Resolved ({{ $counts['resolved'] }})</option>
      <option value="rejected"   {{ $status==='rejected'   ? 'selected':'' }}>Rejected ({{ $counts['rejected'] }})</option>
    </select>
    <button class="btn-admin btn-admin--primary" type="submit">Filter</button>
    <a href="{{ route('admin.complaints.index') }}" class="btn-admin btn-admin--ghost">Reset</a>
  </form>
</div>

@include('partials.history-period', ['period' => $period, 'keep' => ['q' => request('q'), 'status' => $status]])

<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Ref</th><th>Customer</th><th>Order</th><th>Subject</th>
          <th>Mobile</th><th>Status</th><th>Opened</th><th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($complaints as $c)
        <tr>
          <td><b><a href="{{ route('admin.complaints.show', $c) }}" style="color:var(--gold-500);">{{ $c->reference }}</a></b></td>
          <td>
            {{ $c->user->name }}<br>
            <small style="color:var(--muted)">{{ $c->user->email }}</small>
          </td>
          <td>
            @if($c->order)
              <a href="{{ route('admin.orders.show', $c->order) }}" style="color:var(--gold-500); font-weight:700;">{{ $c->order->reference }}</a><br>
              <small style="color:var(--muted)">{{ optional($c->order->service)->name }}</small>
            @else
              —
            @endif
          </td>
          <td style="max-width:260px;">{{ $c->subject }}</td>
          <td>{{ $c->mobile ?: '—' }}</td>
          <td><span class="pill {{ $c->statusBadgeClass() }}">{{ $c->statusLabel() }}</span></td>
          <td><small>{{ $c->created_at->format('Y-m-d H:i') }}<br>{{ $c->created_at->diffForHumans() }}</small></td>
          <td class="col-actions">
            <div class="td-actions">
              <a href="{{ route('admin.complaints.show', $c) }}" class="btn-admin btn-admin--ghost btn-admin--sm">
                {{ in_array($c->status, ['open','in_progress']) ? 'Reply' : 'View' }}
              </a>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--muted);">{{ $period->emptyMessage('complaints') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div style="margin-top:18px;">{{ $complaints->links() }}</div>
</div>

@endsection
