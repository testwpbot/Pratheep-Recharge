@extends('layouts.admin')
@section('title', 'Add customer')

@section('content')

<div style="margin-bottom:16px;">
  <a href="{{ route('admin.users.index') }}" class="btn-admin btn-admin--ghost btn-admin--sm">← Back to users</a>
</div>

<div class="card" style="max-width:720px;">
  <div class="card__head">
    <div>
      <h3>Add a customer</h3>
      <small style="color:var(--muted); font-weight:600;">They can sign in straight away. Email is marked as confirmed.</small>
    </div>
  </div>

  <form method="POST" action="{{ route('admin.users.store') }}">
    @csrf
    <div class="form-grid">
      <div class="field">
        <label>Full name <span class="req">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" required>
      </div>
      <div class="field">
        <label>Email <span class="req">*</span></label>
        <input type="email" name="email" value="{{ old('email') }}" required>
      </div>
      <div class="field">
        <label>Phone <span class="req">*</span></label>
        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="0771234567" required>
      </div>
      <div class="field">
        <label>Password <span class="req">*</span></label>
        <input type="password" name="password" minlength="8" required autocomplete="new-password">
      </div>
      <div class="field">
        <label>Opening wallet (LKR)</label>
        <input type="number" name="opening_balance" min="0" max="500000" step="0.01" value="{{ old('opening_balance', 0) }}">
        <div class="hint">Leave 0 if they will deposit themselves. They need at least LKR {{ number_format($min, 2) }} to recharge.</div>
      </div>
      <div class="field">
        <label>Retailer?</label>
        <label class="sw" style="margin-top:8px;">
          <input type="hidden" name="is_retailer" value="0">
          <input type="checkbox" name="is_retailer" value="1" {{ old('is_retailer') ? 'checked' : '' }}>
          <span class="sw__slider"></span>
        </label>
        <div class="hint">Turn on if this person gets special pricing.</div>
      </div>
    </div>
    <div style="margin-top:18px; display:flex; justify-content:flex-end; gap:10px;">
      <a href="{{ route('admin.users.index') }}" class="btn-admin btn-admin--ghost">Cancel</a>
      <button type="submit" class="btn-admin btn-admin--gold">
        <span class="btn-label"><x-icon name="plus" :size="14"/> Add customer</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </div>
  </form>
</div>

@endsection
