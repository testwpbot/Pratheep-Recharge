@extends('layouts.admin')
@section('title', $user->name)

@section('content')

<div style="margin-bottom:16px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
  <a href="{{ route('admin.users.index') }}" class="btn-admin btn-admin--ghost btn-admin--sm">← Back to users</a>
  @if(! $user->is_admin)
    <a href="{{ route('admin.special-pricing.edit', $user) }}" class="btn-admin btn-admin--ghost btn-admin--sm">Special pricing</a>
  @endif
</div>

<div class="user-grid">
  <div class="card">
    <div class="card__head">
      <div>
        <h3>{{ $user->name }}</h3>
        <small style="color:var(--muted); font-weight:600;">{{ $user->email }}</small>
      </div>
      @if($user->is_admin)
        <span class="pill pill--processing">{{ $user->adminRoleLabel() }}</span>
      @elseif($user->is_retailer)
        <span class="pill pill--processing">Retailer</span>
      @else
        <span class="pill pill--disabled">Customer</span>
      @endif
    </div>

    <dl class="kv" style="margin-bottom:18px;">
      <dt>Joined</dt><dd>{{ $user->created_at->format('Y-m-d H:i') }}</dd>
      <dt>Last sign in</dt><dd>{{ $user->last_login_at?->format('Y-m-d H:i') ?: 'Never' }}</dd>
      <dt>Last IP</dt><dd>{{ $user->last_login_ip ?: '—' }}</dd>
      <dt>Email</dt><dd>{{ $user->email_verified_at ? 'Confirmed' : 'Not confirmed' }}</dd>
    </dl>

    @if($user->is_admin)
      <div class="alert" style="margin:0; background:#f7f9fd; color:var(--navy-800); border:1px solid var(--line);">
        Admin login details are changed in Settings → Admins. You can still edit this wallet below.
      </div>
    @else
      <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PATCH')
        <div class="form-grid">
          <div class="field">
            <label>Full name <span class="req">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
          </div>
          <div class="field">
            <label>Email <span class="req">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
          </div>
          <div class="field">
            <label>Phone <span class="req">*</span></label>
            <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required>
          </div>
          <div class="field">
            <label>New password</label>
            <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current">
          </div>
          <div class="field">
            <label>Retailer?</label>
            <label class="sw" style="margin-top:8px;">
              <input type="hidden" name="is_retailer" value="0">
              <input type="checkbox" name="is_retailer" value="1" {{ old('is_retailer', $user->is_retailer) ? 'checked' : '' }}>
              <span class="sw__slider"></span>
            </label>
          </div>
          <div class="field">
            <label>Email confirmed?</label>
            <label class="sw" style="margin-top:8px;">
              <input type="hidden" name="email_verified" value="0">
              <input type="checkbox" name="email_verified" value="1" {{ old('email_verified', (bool) $user->email_verified_at) ? 'checked' : '' }}>
              <span class="sw__slider"></span>
            </label>
          </div>
        </div>
        <div style="margin-top:16px; display:flex; justify-content:flex-end;">
          <button type="submit" class="btn-admin btn-admin--gold">
            <span class="btn-label"><x-icon name="check" :size="14"/> Save details</span>
            <span class="btn-spinner" hidden></span>
          </button>
        </div>
      </form>
    @endif
  </div>

  <div class="card">
    <div class="card__head">
      <h3>Wallet</h3>
    </div>

    <div class="wallet-hero" style="margin-bottom:16px;">
      <small>Wallet now</small>
      <b>LKR {{ number_format($wallet->balance, 2) }}</b>
      @if($notice)
        <p class="wallet-hero__note">{{ $notice['message'] }}</p>
      @elseif(! $user->is_admin)
        <p class="wallet-hero__note">Customer needs at least LKR {{ number_format($min, 2) }} to place a recharge.</p>
      @endif
    </div>

    <form method="POST" action="{{ route('admin.users.wallet', $user) }}">
      @csrf
      <div class="field" style="margin-bottom:14px;">
        <label>What do you want to do? <span class="req">*</span></label>
        <div class="hpr-dd hpr-dd--block" data-hpr-dd>
          <input type="hidden" name="mode" value="{{ old('mode', 'add') }}">
          <button type="button" class="hpr-dd__btn">
            <span class="hpr-dd__label">
              @if(old('mode')==='remove') Take money out
              @elseif(old('mode')==='set') Set exact amount
              @else Add money
              @endif
            </span>
            <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
          </button>
          <div class="hpr-dd__menu" hidden>
            <button type="button" class="hpr-dd__item {{ old('mode','add')==='add' ? 'is-active' : '' }}" data-value="add" data-label="Add money">Add money</button>
            <button type="button" class="hpr-dd__item {{ old('mode')==='remove' ? 'is-active' : '' }}" data-value="remove" data-label="Take money out">Take money out</button>
            <button type="button" class="hpr-dd__item {{ old('mode')==='set' ? 'is-active' : '' }}" data-value="set" data-label="Set exact amount">Set exact amount</button>
          </div>
        </div>
      </div>
      <div class="field" style="margin-bottom:14px;">
        <label>Amount (LKR) <span class="req">*</span></label>
        <input type="number" name="amount" min="0" max="500000" step="0.01" value="{{ old('amount') }}" required placeholder="e.g. 500">
      </div>
      <div class="field" style="margin-bottom:16px;">
        <label>Why are you changing this wallet? <span class="req">*</span></label>
        <textarea name="note" rows="3" required maxlength="500" placeholder="e.g. Cash received at the shop / correction after a failed order">{{ old('note') }}</textarea>
      </div>
      <button type="submit" class="btn-admin btn-admin--gold" style="width:100%; height:46px;">
        <span class="btn-label"><x-icon name="wallet" :size="14"/> Save wallet change</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </form>
  </div>
</div>

<div class="card" style="margin-top:20px;">
  <div class="card__head">
    <h3>Wallet activity</h3>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>When</th>
          <th>Type</th>
          <th>Amount</th>
          <th>Before → After</th>
          <th>Note</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($transactions as $t)
        @php $pos = $t->isCredit(); @endphp
        <tr>
          <td><small>{{ $t->created_at->format('Y-m-d H:i') }}</small></td>
          <td><span class="pill pill--{{ $pos ? 'success' : 'failed' }}">{{ ucfirst($t->type) }}</span></td>
          <td>
            <b class="{{ $pos ? 'tx-row__amt--pos' : 'tx-row__amt--neg' }}">
              {{ $pos ? '+' : '−' }} LKR {{ number_format(abs((float) $t->amount), 2) }}
            </b>
          </td>
          <td>
            <small>LKR {{ number_format((float) $t->balance_before, 2) }} → LKR {{ number_format((float) $t->balance_after, 2) }}</small>
          </td>
          <td><small>{{ $t->description }}</small></td>
        </tr>
      @empty
        <tr><td colspan="5" style="text-align:center; padding:24px; color:var(--muted);">No wallet activity yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="fund-split" style="margin-top:20px;">
  <div class="card" style="margin:0;">
    <div class="card__head">
      <h3>Recent orders</h3>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Ref</th><th>Service</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($orders as $o)
          <tr>
            <td><a href="{{ route('admin.orders.show', $o) }}" style="color:var(--gold-500); font-weight:700;">{{ $o->reference }}</a></td>
            <td>{{ $o->service->name ?? '—' }}</td>
            <td>LKR {{ number_format((float) $o->amount, 2) }}</td>
            <td><span class="pill pill--{{ $o->status }}">{{ $o->statusLabel() }}</span></td>
          </tr>
        @empty
          <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--muted);">No orders yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card" style="margin:0;">
    <div class="card__head">
      <h3>Deposits</h3>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>When</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($deposits as $d)
          <tr>
            <td><a href="{{ route('admin.deposits.show', $d) }}" style="color:var(--gold-500); font-weight:700;">{{ $d->created_at->format('Y-m-d H:i') }}</a></td>
            <td>LKR {{ number_format((float) $d->amount, 2) }}</td>
            <td><span class="pill pill--{{ $d->status === 'approved' ? 'success' : ($d->status === 'pending' ? 'pending' : 'failed') }}">{{ ucfirst($d->status) }}</span></td>
          </tr>
        @empty
          <tr><td colspan="3" style="text-align:center; padding:20px; color:var(--muted);">No deposits yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
.user-grid{
  display:grid; gap:20px; grid-template-columns:1.1fr .9fr; align-items:start;
}
.wallet-hero__note{
  position:relative;
  margin:10px 0 0;
  font-size:13.5px;
  font-weight:600;
  line-height:1.45;
  color:rgba(255,255,255,.82);
}
@media (max-width:900px){
  .user-grid{grid-template-columns:1fr;}
}
</style>
@endpush
