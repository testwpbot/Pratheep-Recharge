@extends('layouts.admin')
@section('title', 'Wallet Deposits')

@section('content')

<div class="dep-stats">
  <a href="?status=pending" class="dep-stat dep-stat--pend" style="text-decoration:none; color:inherit;">
    <span>Pending</span>
    <b>{{ $counts['pending'] }}</b>
  </a>
  <a href="?status=approved" class="dep-stat dep-stat--app" style="text-decoration:none; color:inherit;">
    <span>Approved</span>
    <b>{{ $counts['approved'] }}</b>
  </a>
  <a href="?status=rejected" class="dep-stat dep-stat--rej" style="text-decoration:none; color:inherit;">
    <span>Rejected</span>
    <b>{{ $counts['rejected'] }}</b>
  </a>
  <a href="?status=all" class="dep-stat" style="text-decoration:none; color:inherit;">
    <span>All Time</span>
    <b>{{ $counts['pending'] + $counts['approved'] + $counts['rejected'] }}</b>
  </a>
</div>

<div class="card">
  <div class="card__head">
    <h3>{{ ucfirst($status) }} Deposits</h3>
  </div>

  @if($deposits->isEmpty())
    <div style="text-align:center; padding:40px; color:var(--muted);">No {{ $status }} deposits.</div>
  @else
    <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Customer</th>
          <th>Amount</th>
          <th>Bank / Depositor</th>
          <th>Date</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($deposits as $d)
          <tr>
            <td><b>{{ $d->reference() }}</b></td>
            <td>
              <b>{{ $d->user->name }}</b><br>
              <small style="color:var(--muted); font-weight:600;">{{ $d->user->email }} · {{ $d->user->phone }}</small>
            </td>
            <td><b style="color:var(--navy-900); font-size:15px;">LKR {{ number_format($d->amount, 2) }}</b></td>
            <td>
              <b>{{ $d->bank_name }}</b><br>
              <small style="color:var(--muted); font-weight:600;">{{ $d->depositor_name }}</small>
            </td>
            <td><small style="font-weight:600;">{{ $d->created_at->format('Y-m-d H:i') }}</small></td>
            <td>
              @if($d->status === 'pending')
                <span class="pill pill--pending">Pending</span>
              @elseif($d->status === 'approved')
                <span class="pill pill--success">Approved</span>
              @else
                <span class="pill pill--failed">Rejected</span>
              @endif
            </td>
            <td>
              <a href="{{ route('admin.deposits.show', $d) }}" class="btn-admin btn-admin--ghost btn-admin--sm">
                <x-icon name="eye" :size="12"/> Review
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    <div style="margin-top:18px;">
      {{ $deposits->links() }}
    </div>
  @endif
</div>

@endsection
