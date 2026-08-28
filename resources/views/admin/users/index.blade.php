@extends('layouts.admin')
@section('title', 'Users')

@section('content')

<div class="stats-grid">
  <div class="stat"><b>{{ number_format($stats['customers']) }}</b><span>Customers</span></div>
  <div class="stat"><b>LKR {{ number_format($stats['wallet_total'], 2) }}</b><span>Customer wallets</span></div>
  <div class="stat"><b>{{ number_format($stats['low']) }}</b><span>Below LKR {{ number_format($min, 0) }}</span></div>
  <div class="stat"><b>{{ number_format($stats['retailers']) }}</b><span>Retailers</span></div>
</div>

<div class="toolbar">
  <form method="GET" action="{{ route('admin.users.index') }}" id="userFilterForm" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; width:100%;">
    <input type="text" name="q" value="{{ $search }}" placeholder="Search name, email, phone…" style="min-width:240px; flex:1;">

    <div class="hpr-dd" data-hpr-dd data-auto-submit="userFilterForm">
      <input type="hidden" name="filter" value="{{ $filter }}">
      <button type="button" class="hpr-dd__btn">
        <span class="hpr-dd__label">
          @if($filter==='retailers') Retailers
          @elseif($filter==='admins') Admins
          @elseif($filter==='low') Wallet below LKR {{ number_format($min, 0) }}
          @elseif($filter==='all') Everyone
          @else Customers
          @endif
        </span>
        <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </button>
      <div class="hpr-dd__menu" hidden>
        <button type="button" class="hpr-dd__item {{ $filter==='customers' ? 'is-active' : '' }}" data-value="customers" data-label="Customers">Customers</button>
        <button type="button" class="hpr-dd__item {{ $filter==='retailers' ? 'is-active' : '' }}" data-value="retailers" data-label="Retailers">Retailers</button>
        <button type="button" class="hpr-dd__item {{ $filter==='low' ? 'is-active' : '' }}" data-value="low" data-label="Wallet below LKR {{ number_format($min, 0) }}">Wallet below LKR {{ number_format($min, 0) }}</button>
        <button type="button" class="hpr-dd__item {{ $filter==='admins' ? 'is-active' : '' }}" data-value="admins" data-label="Admins">Admins</button>
        <button type="button" class="hpr-dd__item {{ $filter==='all' ? 'is-active' : '' }}" data-value="all" data-label="Everyone">Everyone</button>
      </div>
    </div>

    <button class="btn-admin btn-admin--primary" type="submit">Search</button>
    <a href="{{ route('admin.users.index') }}" class="btn-admin btn-admin--ghost">Reset</a>
    <a href="{{ route('admin.users.create') }}" class="btn-admin btn-admin--gold">
      <x-icon name="plus" :size="14"/> Add customer
    </a>
  </form>
</div>

<div class="card">
  <div class="card__head">
    <h3>Users</h3>
    <small style="color:var(--muted); font-weight:600;">Use Wallet manage to add or deduct funds. Open a person for full details.</small>
  </div>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Person</th>
          <th>Phone</th>
          <th>Type</th>
          <th>Wallet</th>
          <th>Orders</th>
          <th>Joined</th>
          <th class="col-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($users as $u)
        @php
          $bal = (float) ($u->wallet->balance ?? 0);
          $low = ! $u->is_admin && $bal + 0.0001 < $min;
        @endphp
        <tr>
          <td>
            <b>{{ $u->name }}</b><br>
            <small style="color:var(--muted)">{{ $u->email }}</small>
          </td>
          <td>{{ $u->phone }}</td>
          <td>
            @if($u->is_admin)
              <span class="pill pill--processing">{{ $u->adminRoleLabel() }}</span>
            @elseif($u->is_retailer)
              <span class="pill pill--processing">Retailer</span>
            @else
              <span class="pill pill--disabled">Customer</span>
            @endif
          </td>
          <td>
            <b style="{{ $low ? 'color:#a52222;' : '' }}">LKR {{ number_format($bal, 2) }}</b>
            @if($low)
              <br><small style="color:#a52222; font-weight:700;">Low</small>
            @endif
          </td>
          <td>{{ number_format($u->orders_count) }}</td>
          <td><small>{{ $u->created_at->format('Y-m-d') }}</small></td>
          <td class="col-actions">
            <div class="td-actions">
              <button type="button"
                      class="btn-admin btn-admin--primary btn-admin--sm"
                      data-wallet-manage
                      data-action="{{ route('admin.users.wallet', $u) }}"
                      data-name="{{ $u->name }}"
                      data-email="{{ $u->email }}"
                      data-balance="{{ number_format($bal, 2) }}">
                Wallet manage
              </button>
              <a href="{{ route('admin.users.show', $u) }}" class="btn-admin btn-admin--gold btn-admin--sm">Open</a>
              @unless($u->is_admin)
                <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Delete {{ addslashes($u->name) }}? Their wallet and orders will also be removed.');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn-admin btn-admin--danger btn-admin--sm">Delete</button>
                </form>
              @endunless
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--muted);">No users match this search.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div style="margin-top:18px;">{{ $users->links() }}</div>
</div>

<div class="rc-modal" id="walletManageModal" hidden>
  <div class="rc-modal__backdrop" data-wallet-close></div>
  <div class="rc-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="wmHead">
    <button type="button" class="rc-modal__close" data-wallet-close aria-label="Close">✕</button>
    <div class="rc-modal__head">
      <h3 id="wmHead">Wallet manage</h3>
      <small id="wmWho">—</small>
    </div>
    <p style="margin:0 0 16px; font-weight:700; color:var(--navy-900);">
      Wallet now: <span id="wmBalance">LKR 0.00</span>
    </p>
    <form method="POST" action="#" id="walletManageForm">
      @csrf
      <div class="field" style="margin-bottom:14px;">
        <label>What do you want to do? <span class="req">*</span></label>
        <div class="hpr-dd hpr-dd--block" data-hpr-dd>
          <input type="hidden" name="mode" id="wmMode" value="add">
          <button type="button" class="hpr-dd__btn">
            <span class="hpr-dd__label">Add funds</span>
            <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </button>
          <div class="hpr-dd__menu" hidden>
            <button type="button" class="hpr-dd__item is-active" data-value="add" data-label="Add funds">Add funds</button>
            <button type="button" class="hpr-dd__item" data-value="remove" data-label="Deduct funds">Deduct funds</button>
            <button type="button" class="hpr-dd__item" data-value="set" data-label="Set exact amount">Set exact amount</button>
          </div>
        </div>
      </div>
      <div class="field" style="margin-bottom:14px;">
        <label>Amount (LKR) <span class="req">*</span></label>
        <input type="number" name="amount" min="0" max="500000" step="0.01" required placeholder="e.g. 500">
      </div>
      <div class="field" style="margin-bottom:16px;">
        <label>Reason <span class="req">*</span></label>
        <textarea name="note" rows="3" required maxlength="500" placeholder="e.g. Cash received at the shop"></textarea>
      </div>
      <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
        <button type="button" class="btn-admin btn-admin--ghost" data-wallet-close>Cancel</button>
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label">Save wallet change</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
(function(){
  var modal = document.getElementById('walletManageModal');
  var form = document.getElementById('walletManageForm');
  var who = document.getElementById('wmWho');
  var bal = document.getElementById('wmBalance');
  var mode = document.getElementById('wmMode');
  if (!modal || !form) return;
  document.body.appendChild(modal);

  function open(btn){
    form.action = btn.dataset.action;
    who.textContent = (btn.dataset.name || '') + (btn.dataset.email ? ' · ' + btn.dataset.email : '');
    bal.textContent = 'LKR ' + (btn.dataset.balance || '0.00');
    form.reset();
    mode.value = 'add';
    var label = modal.querySelector('.hpr-dd__label');
    if (label) label.textContent = 'Add funds';
    modal.querySelectorAll('.hpr-dd__item').forEach(function(it){
      it.classList.toggle('is-active', it.getAttribute('data-value') === 'add');
    });
    modal.hidden = false;
  }
  function close(){ modal.hidden = true; }

  document.querySelectorAll('[data-wallet-manage]').forEach(function(btn){
    btn.addEventListener('click', function(){ open(btn); });
  });
  modal.querySelectorAll('[data-wallet-close]').forEach(function(el){
    el.addEventListener('click', close);
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && !modal.hidden) close();
  });
})();
</script>
@endpush
