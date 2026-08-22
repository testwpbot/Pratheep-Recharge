@extends('layouts.admin')
@section('title', 'Settings')

@section('content')

<div class="card">
  <div class="card__head">
    <h3>Site Settings</h3>
  </div>

  <div class="set-tabs" role="tablist">
    <button type="button" class="set-tab active" data-set-tab="general">General</button>
    <button type="button" class="set-tab" data-set-tab="bank">Bank Details</button>
    <button type="button" class="set-tab" data-set-tab="smtp">SMTP / Email</button>
    @if($isMainAdmin)
      <button type="button" class="set-tab" data-set-tab="admins">Admins</button>
    @endif
  </div>

  {{-- ===== GENERAL ===== --}}
  <div class="set-panel active" data-set-panel="general">
    <form method="POST" action="{{ route('admin.settings.general') }}" data-ajax>
      @csrf
      <div class="form-grid">
        <div class="field">
          <label>Site Name</label>
          <input type="text" name="site_name" value="{{ old('site_name', $general['site_name'] ?? 'Happy Pratheep Recharge') }}" required>
        </div>
        <div class="field">
          <label>Support Email</label>
          <input type="email" name="support_email" value="{{ old('support_email', $general['support_email'] ?? '') }}" placeholder="admin@happypratheep.lk">
          <div class="hint">Deposit requests and “provider money is low” emails are sent here.</div>
        </div>
        <div class="field">
          <label>Support Phone</label>
          <input type="text" name="support_phone" value="{{ old('support_phone', $general['support_phone'] ?? '') }}" placeholder="+94 77 123 4567">
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Deposit Note (shown to customers)</label>
          <textarea name="deposit_note" rows="3" placeholder="e.g. Please include your registered phone number in the payment reference so we can credit quickly.">{{ old('deposit_note', $general['deposit_note'] ?? '') }}</textarea>
        </div>
      </div>
      <div style="margin-top:18px; display:flex; justify-content:flex-end;">
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="check" :size="14"/> Save General Settings</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>

  {{-- ===== BANK ===== --}}
  <div class="set-panel" data-set-panel="bank">
    <p style="color:var(--muted); font-size:13px; font-weight:600; margin:0 0 14px;">These bank details are shown to customers on the Wallet Deposit page. Only one receiving bank account is supported at the moment.</p>
    <form method="POST" action="{{ route('admin.settings.bank') }}" data-ajax>
      @csrf
      <div class="form-grid">
        <div class="field">
          <label>Bank Name <span class="req">*</span></label>
          <input type="text" name="bank_name" value="{{ old('bank_name', $bank['bank_name'] ?? '') }}" placeholder="e.g. Commercial Bank" required>
        </div>
        <div class="field">
          <label>Account Name <span class="req">*</span></label>
          <input type="text" name="account_name" value="{{ old('account_name', $bank['account_name'] ?? '') }}" placeholder="e.g. Happy Pratheep Recharge (Pvt) Ltd" required>
        </div>
        <div class="field">
          <label>Account Number <span class="req">*</span></label>
          <input type="text" name="account_no" value="{{ old('account_no', $bank['account_no'] ?? '') }}" placeholder="e.g. 8001234567890" required>
        </div>
        <div class="field">
          <label>Branch</label>
          <input type="text" name="branch" value="{{ old('branch', $bank['branch'] ?? '') }}" placeholder="e.g. Kandy">
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Transfer Instructions</label>
          <textarea name="instructions" rows="4" placeholder="e.g. After sending the deposit, upload a clear photo/screenshot of the bank slip below. Include your registered phone number as the payment reference.">{{ old('instructions', $bank['instructions'] ?? '') }}</textarea>
        </div>
      </div>
      <div style="margin-top:18px; display:flex; justify-content:flex-end;">
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="check" :size="14"/> Save Bank Details</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>

  {{-- ===== SMTP ===== --}}
  <div class="set-panel" data-set-panel="smtp">
    <p style="color:var(--muted); font-size:13px; font-weight:600; margin:0 0 14px;">Configure the outgoing email server. Deposit notifications, approval/rejection emails and other system emails use these settings. Leave the password field blank to keep the current password.</p>
    <form method="POST" action="{{ route('admin.settings.smtp') }}" data-ajax id="smtpForm">
      @csrf
      <div class="form-grid">
        <div class="field" style="grid-column:span 2;">
          <label>SMTP Host <span class="req">*</span></label>
          <input type="text" name="host" value="{{ old('host', $smtp['host'] ?? '') }}" placeholder="smtp.gmail.com / smtp.zoho.com / mail.yourdomain.com" required>
        </div>
        <div class="field">
          <label>Port <span class="req">*</span></label>
          <input type="number" name="port" value="{{ old('port', $smtp['port'] ?? 587) }}" required min="1" max="65535">
        </div>
        <div class="field">
          <label>Encryption</label>
          <select name="encryption">
            <option value="tls" {{ ($smtp['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
            <option value="ssl" {{ ($smtp['encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
            <option value="none" {{ ($smtp['encryption'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
          </select>
        </div>
        <div class="field">
          <label>SMTP Username</label>
          <input type="text" name="username" value="{{ old('username', $smtp['username'] ?? '') }}" autocomplete="off">
        </div>
        <div class="field">
          <label>SMTP Password</label>
          <input type="password" name="password" placeholder="Leave blank to keep current" autocomplete="new-password">
        </div>
        <div class="field">
          <label>From Address <span class="req">*</span></label>
          <input type="email" name="from_address" value="{{ old('from_address', $smtp['from_address'] ?? 'noreply@happypratheep.lk') }}" required>
        </div>
        <div class="field">
          <label>From Name <span class="req">*</span></label>
          <input type="text" name="from_name" value="{{ old('from_name', $smtp['from_name'] ?? 'Happy Pratheep Recharge') }}" required>
        </div>
      </div>

      <div class="test-smtp-row">
        <input type="email" id="testTo" placeholder="Send test email to…" style="height:40px; padding:0 12px; border-radius:10px; border:1.6px solid rgba(11,42,91,.16); font:inherit; font-size:14px;">
        <button type="button" class="btn-admin btn-admin--ghost btn-admin--sm" id="testSmtpBtn">
          <span class="btn-label"><x-icon name="mail" :size="13"/> Send Test</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
      <div class="hint" id="testSmtpResult" style="margin-top:8px;"></div>

      <div style="margin-top:18px; display:flex; justify-content:flex-end;">
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="check" :size="14"/> Save SMTP Settings</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>

  @if($isMainAdmin)
  <div class="set-panel" data-set-panel="admins">
    <p style="color:var(--muted); font-size:13px; font-weight:600; margin:0 0 16px;">
      Main admin can add people who can open the admin panel. <b>Main admin</b> can add or remove admins.
      <b>Admin</b> can run the site, but cannot add other admins.
    </p>

    <form method="POST" action="{{ route('admin.settings.admins.store') }}" style="margin-bottom:22px; padding:16px; border:1px solid var(--line); border-radius:14px; background:#f7f9fd;">
      @csrf
      <div class="form-grid">
        <div class="field">
          <label>Name <span class="req">*</span></label>
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
          <label>Password</label>
          <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Needed for a new person">
          <div class="hint">If this email already has a customer account, we promote them and password can stay empty.</div>
        </div>
        <div class="field">
          <label>Role <span class="req">*</span></label>
          <select name="role" required>
            <option value="admin" @selected(old('role','admin')==='admin')>Admin</option>
            <option value="main" @selected(old('role')==='main')>Main admin</option>
          </select>
        </div>
      </div>
      <div style="margin-top:16px; display:flex; justify-content:flex-end;">
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="plus" :size="14"/> Add admin</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th class="col-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($admins as $a)
          <tr>
            <td>
              <b>{{ $a->name }}</b>
              @if($a->id === auth()->id())<br><small style="color:var(--muted);">you</small>@endif
            </td>
            <td>{{ $a->email }}</td>
            <td>{{ $a->phone }}</td>
            <td>
              <form method="POST" action="{{ route('admin.settings.admins.update', $a) }}">
                @csrf
                @method('PATCH')
                <select name="role" onchange="this.form.submit()" style="height:36px; padding:0 10px; border-radius:9px; border:1.6px solid rgba(11,42,91,.16); font:inherit; font-weight:700; font-size:13px;">
                  <option value="admin" @selected(!$a->isMainAdmin())>Admin</option>
                  <option value="main" @selected($a->isMainAdmin())>Main admin</option>
                </select>
              </form>
            </td>
            <td class="col-actions">
              <div class="td-actions">
                @if($a->id !== auth()->id())
                  <form method="POST" action="{{ route('admin.settings.admins.destroy', $a) }}" onsubmit="return confirm('Remove admin access for {{ addslashes($a->name) }}? They can still sign in as a customer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-admin btn-admin--danger btn-admin--sm">Remove</button>
                  </form>
                @else
                  <em style="color:var(--muted); font-size:12px;">Your account</em>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center; color:var(--muted); padding:24px;">No admins yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @endif
</div>

@endsection

@push('scripts')
<script>
(function(){
  // Tab switching
  var tabs = document.querySelectorAll('.set-tab');
  var panels = document.querySelectorAll('.set-panel');
  function openTab(key){
    var found = false;
    tabs.forEach(function(x){
      var on = x.dataset.setTab === key;
      x.classList.toggle('active', on);
      if (on) found = true;
    });
    if (!found) return;
    panels.forEach(function(p){ p.classList.toggle('active', p.dataset.setPanel === key); });
  }
  tabs.forEach(function(t){
    t.addEventListener('click', function(){ openTab(t.dataset.setTab); });
  });
  var q = new URLSearchParams(window.location.search).get('tab');
  if (q) openTab(q);

  // SMTP test
  var testBtn = document.getElementById('testSmtpBtn');
  var testTo  = document.getElementById('testTo');
  var result  = document.getElementById('testSmtpResult');
  if (testBtn){
    testBtn.addEventListener('click', function(){
      var to = testTo.value.trim();
      if (!to){ testTo.focus(); return; }
      var form = document.getElementById('smtpForm');
      var fd = new FormData(form);
      fd.append('to', to);

      var started = performance.now();
      var MIN_MS = 2200;
      if (typeof setBtnLoading === 'function') setBtnLoading(testBtn, true);
      else { testBtn.disabled = true; testBtn.classList.add('is-loading'); var sp0=testBtn.querySelector('.btn-spinner'); if(sp0) sp0.hidden=false; }
      result.textContent = 'Sending…'; result.style.color = 'var(--muted)';

      fetch('{{ route('admin.settings.test-smtp') }}', {
        method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, credentials:'same-origin'
      })
      .then(function(r){ return r.json().then(function(d){ return {ok:r.ok, d:d}; }); })
      .then(function(res){
        var elapsed = performance.now() - started;
        var wait = Math.max(0, MIN_MS - elapsed);
        setTimeout(function(){
          if (typeof setBtnLoading === 'function') setBtnLoading(testBtn, false);
          else { testBtn.disabled = false; testBtn.classList.remove('is-loading'); var sp1=testBtn.querySelector('.btn-spinner'); if(sp1) sp1.hidden=true; }
          if (res.ok && res.d.ok){
            result.textContent = '✅ ' + (res.d.message || 'Test email sent.');
            result.style.color = '#1c7a49';
          } else {
            result.textContent = '❌ ' + (res.d.message || 'Test failed.');
            result.style.color = '#b42f2f';
          }
        }, wait);
      })
      .catch(function(e){
        var elapsed = performance.now() - started;
        var wait = Math.max(0, MIN_MS - elapsed);
        setTimeout(function(){
          if (typeof setBtnLoading === 'function') setBtnLoading(testBtn, false);
          else { testBtn.disabled = false; testBtn.classList.remove('is-loading'); var sp2=testBtn.querySelector('.btn-spinner'); if(sp2) sp2.hidden=true; }
          result.textContent = '❌ ' + e.message;
          result.style.color = '#b42f2f';
        }, wait);
      });
    });
  }
})();
</script>
@endpush
