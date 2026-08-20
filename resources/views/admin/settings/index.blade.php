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
          <div class="hint">Deposit request emails are sent here.</div>
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
</div>

@endsection

@push('scripts')
<script>
(function(){
  // Tab switching
  var tabs = document.querySelectorAll('.set-tab');
  var panels = document.querySelectorAll('.set-panel');
  tabs.forEach(function(t){
    t.addEventListener('click', function(){
      var key = t.dataset.setTab;
      tabs.forEach(function(x){ x.classList.toggle('active', x===t); });
      panels.forEach(function(p){ p.classList.toggle('active', p.dataset.setPanel === key); });
    });
  });

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
