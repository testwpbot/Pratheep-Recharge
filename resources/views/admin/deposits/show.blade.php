@extends('layouts.admin')
@section('title', 'Deposit ' . $deposit->reference())

@section('content')

<div style="margin-bottom:16px;">
  <a href="{{ route('admin.deposits.index', ['status' => $deposit->status]) }}" class="btn-admin btn-admin--ghost btn-admin--sm">
    ← Back to deposits
  </a>
</div>

<div class="card">
  <div class="card__head">
    <div>
      <h3>Deposit #{{ $deposit->reference() }}</h3>
      <small style="color:var(--muted); font-weight:600;">Submitted {{ $deposit->created_at->format('Y-m-d H:i') }}</small>
    </div>
    @if($deposit->status === 'pending')
      <span class="pill pill--pending">Pending Review</span>
    @elseif($deposit->status === 'approved')
      <span class="pill pill--success">Approved</span>
    @else
      <span class="pill pill--failed">Rejected</span>
    @endif
  </div>

  <div class="dep-detail-grid">
    <div>
      <h4 style="margin:0 0 12px; font-size:15px; color:var(--navy-900);">Customer</h4>
      <dl class="kv">
        <dt>Name</dt><dd>{{ $deposit->user->name }}</dd>
        <dt>Email</dt><dd>{{ $deposit->user->email }}</dd>
        <dt>Phone</dt><dd>{{ $deposit->user->phone }}</dd>
      </dl>

      <h4 style="margin:24px 0 12px; font-size:15px; color:var(--navy-900);">Payment Details</h4>
      <dl class="kv">
        <dt>Amount</dt><dd><b style="font-size:18px; color:var(--navy-900);">LKR {{ number_format($deposit->amount, 2) }}</b></dd>
        <dt>Bank</dt><dd>{{ $deposit->bank_name }}</dd>
        <dt>Depositor</dt><dd>{{ $deposit->depositor_name }}</dd>
        <dt>Bank Ref #</dt><dd>{{ $deposit->reference_number ?: '—' }}</dd>
      </dl>

      @if($deposit->status !== 'pending')
        <h4 style="margin:24px 0 12px; font-size:15px; color:var(--navy-900);">Review</h4>
        <dl class="kv">
          @if($deposit->approver)
            <dt>Reviewed by</dt><dd>{{ $deposit->approver->name }}</dd>
          @endif
          <dt>{{ $deposit->status === 'approved' ? 'Approved at' : 'Rejected at' }}</dt>
          <dd>{{ $deposit->status === 'approved' ? $deposit->approved_at?->format('Y-m-d H:i') : $deposit->rejected_at?->format('Y-m-d H:i') }}</dd>
          @if($deposit->admin_note)
            <dt>Note</dt><dd style="white-space:pre-wrap;">{{ $deposit->admin_note }}</dd>
          @endif
        </dl>
      @endif

      @if($deposit->status === 'pending')
        <div style="margin-top:24px; display:flex; gap:10px; flex-wrap:wrap;">
          <button type="button" class="btn-admin btn-admin--gold" id="approveBtn">
            <span class="btn-label"><x-icon name="check" :size="14"/> Approve & Credit Wallet</span>
            <span class="btn-spinner" hidden></span>
          </button>
          <button type="button" class="btn-admin btn-admin--danger" id="rejectBtn">
            <span class="btn-label"><x-icon name="ban" :size="14"/> Reject</span>
            <span class="btn-spinner" hidden></span>
          </button>
        </div>
      @endif
    </div>

    <div>
      <h4 style="margin:0 0 12px; font-size:15px; color:var(--navy-900);">Bank Slip</h4>
      @if($deposit->slip_path)
        <div class="slip-preview">
          @if(str_ends_with(strtolower($deposit->slip_path), '.pdf'))
            <a href="{{ asset('storage/' . $deposit->slip_path) }}" target="_blank" class="btn-admin btn-admin--primary" style="width:100%; padding:40px 20px;">
              <x-icon name="bill" :size="22"/> View PDF Slip
            </a>
          @else
            <a href="{{ asset('storage/' . $deposit->slip_path) }}" target="_blank">
              <img src="{{ asset('storage/' . $deposit->slip_path) }}" alt="Bank slip">
            </a>
          @endif
        </div>
        <p style="font-size:12px; color:var(--muted); margin-top:8px;">Click the slip to open full-size in a new tab.</p>
      @else
        <div style="padding:30px; background:#f7f9fd; border-radius:12px; color:var(--muted); text-align:center;">No slip uploaded.</div>
      @endif
    </div>
  </div>
</div>

@if($deposit->status === 'pending')
{{-- Approve note modal --}}
<div class="rc-modal" id="approveModal" hidden aria-hidden="true">
  <div class="rc-modal__backdrop" data-ap-close></div>
  <div class="rc-modal__dialog" role="dialog" aria-modal="true" style="max-width:480px;">
    <button type="button" class="rc-modal__close" data-ap-close aria-label="Close"><x-icon name="x" :size="18"/></button>
    <div class="rc-modal__head" style="border:0; padding-bottom:0;">
      <div>
        <h3>Approve Deposit</h3>
        <small>LKR {{ number_format($deposit->amount, 2) }} → {{ $deposit->user->name }}</small>
      </div>
    </div>
    <form id="approveForm" style="padding:0 4px;" data-deposit-form data-no-auto-spin>
      @csrf
      <div class="field" style="margin-bottom:14px; margin-top:14px;">
        <label>Admin Note <small style="color:var(--muted); font-weight:600;">(optional — sent to customer by email)</small></label>
        <textarea name="admin_note" rows="3" placeholder="e.g. Verified and credited. Thank you!"></textarea>
      </div>
      <p style="font-size:13px; color:var(--muted); font-weight:600; margin:0 0 14px;">
        On approval the customer's wallet will be credited with <b style="color:#1c7a49;">LKR {{ number_format($deposit->amount, 2) }}</b> and a confirmation email will be sent.
      </p>
      <button type="submit" class="btn-admin btn-admin--gold" style="width:100%; height:46px;">
        <span class="btn-label"><x-icon name="check" :size="14"/> Confirm Approve</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </form>
  </div>
</div>

{{-- Reject note modal --}}
<div class="rc-modal" id="rejectModal" hidden aria-hidden="true">
  <div class="rc-modal__backdrop" data-rj-close></div>
  <div class="rc-modal__dialog" role="dialog" aria-modal="true" style="max-width:480px;">
    <button type="button" class="rc-modal__close" data-rj-close aria-label="Close"><x-icon name="x" :size="18"/></button>
    <div class="rc-modal__head" style="border:0; padding-bottom:0;">
      <div>
        <h3 style="color:#b42f2f;">Reject Deposit</h3>
        <small>LKR {{ number_format($deposit->amount, 2) }} from {{ $deposit->user->name }}</small>
      </div>
    </div>
    <form id="rejectForm" style="padding:0 4px;" data-deposit-form data-no-auto-spin>
      @csrf
      <div class="field" style="margin-bottom:14px; margin-top:14px;">
        <label>Reason for rejection <span class="req">*</span></label>
        <textarea name="admin_note" rows="3" required placeholder="e.g. Slip image unclear / amount mismatch. Please upload a clear slip and try again."></textarea>
      </div>
      <p style="font-size:13px; color:var(--muted); font-weight:600; margin:0 0 14px;">
        The customer will be emailed with this reason. No balance will be credited.
      </p>
      <button type="submit" class="btn-admin btn-admin--danger" style="width:100%; height:46px; background:#dc2626; color:#fff;">
        <span class="btn-label"><x-icon name="ban" :size="14"/> Confirm Reject</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </form>
  </div>
</div>

{{-- "Are you sure?" second-step confirm modal --}}
<div class="rc-modal" id="confirmModal" hidden aria-hidden="true">
  <div class="rc-modal__backdrop" data-cfm-close></div>
  <div class="rc-modal__dialog" role="dialog" aria-modal="true" style="max-width:420px; text-align:center;">
    <button type="button" class="rc-modal__close" data-cfm-close aria-label="Close"><x-icon name="x" :size="18"/></button>
    <div style="padding:20px 10px 10px;">
      <div id="cfmIcon" style="width:64px; height:64px; border-radius:50%; margin:0 auto 14px; display:inline-flex; align-items:center; justify-content:center;">
        <span id="cfmIconSvg">
          <x-icon name="check" :size="30"/>
        </span>
      </div>
      <h3 id="cfmTitle" style="margin:0 0 8px;">Are you sure?</h3>
      <p id="cfmText" style="margin:0; color:var(--muted); font-weight:600; font-size:14px; line-height:1.5;">This action cannot be undone.</p>
    </div>
    <div style="display:flex; gap:10px; padding:14px 10px 4px;">
      <button type="button" class="btn-admin btn-admin--ghost" style="flex:1; height:44px;" data-cfm-close>Cancel</button>
      <button type="button" class="btn-admin" id="cfmOk" style="flex:1; height:44px;">
        <span class="btn-label">Yes, proceed</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </div>
  </div>
</div>
@endif

@endsection

@push('styles')
<style>
/* Approve modal icon defaults */
#cfmIcon{
  background:rgba(55,214,122,.12);
  color:#1c7a49;
}
</style>
@endpush

@push('scripts')
@if($deposit->status === 'pending')
<script>
(function(){
  // ---- Stacked modal management with proper scroll lock ----
  var openModals = [];

  function lockScroll(){
    window.addEventListener('wheel', onScroll, {capture:true, passive:false});
    window.addEventListener('touchmove', onScroll, {capture:true, passive:false});
    window.addEventListener('keydown', onKeyScroll, {capture:true});
  }
  function unlockScroll(){
    window.removeEventListener('wheel', onScroll, {capture:true, passive:false});
    window.removeEventListener('touchmove', onScroll, {capture:true, passive:false});
    window.removeEventListener('keydown', onKeyScroll, {capture:true});
  }
  function onScroll(e){
    // Allow scroll inside the dialog content OR on the modal container itself
    // (rc-modal can be overflow-y:auto so long dialogs are scrollable).
    if (e.target.closest('.rc-modal__dialog, .rc-modal')) return;
    e.preventDefault();
  }
  function onKeyScroll(e){
    if (e.key === 'Escape'){
      if (openModals.length){
        e.preventDefault();
        closeModal(openModals[openModals.length-1]);
      }
      return;
    }
    var keys=[32,33,34,35,36,38,40];
    if(keys.indexOf(e.keyCode)===-1) return;
    if (e.target.closest('.rc-modal__dialog, .rc-modal')) return;
    e.preventDefault();
  }

  function openModal(m){
    m.hidden = false;
    m.setAttribute('aria-hidden','false');
    if (openModals.length === 0) lockScroll();
    openModals.push(m);
  }
  function closeModal(m){
    m.hidden = true;
    m.setAttribute('aria-hidden','true');
    var i = openModals.indexOf(m);
    if (i >= 0) openModals.splice(i,1);
    if (openModals.length === 0) unlockScroll();
  }

  function setupModal(btnId, modalId, closeAttr){
    var btn = document.getElementById(btnId);
    var m = document.getElementById(modalId);
    btn.addEventListener('click', function(e){ e.stopPropagation(); openModal(m); });
    m.querySelectorAll('[data-'+closeAttr+']').forEach(function(el){
      el.addEventListener('click', function(){ closeModal(m); });
    });
    return m;
  }

  var apModal = setupModal('approveBtn','approveModal','ap-close');
  var rjModal = setupModal('rejectBtn','rejectModal','rj-close');

  // ---- Confirmation modal (second-step "Are you sure") ----
  var cfm = document.getElementById('confirmModal');
  var cfmTitle = document.getElementById('cfmTitle');
  var cfmText  = document.getElementById('cfmText');
  var cfmIcon  = document.getElementById('cfmIcon');
  var cfmIconSvg = document.getElementById('cfmIconSvg');
  var cfmOk    = document.getElementById('cfmOk');
  var pendingAction = null;

  var CHECK_SVG = '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.2"/><path d="m8.2 12.3 2.6 2.6 5-5.4"/></svg>';
  var BAN_SVG = '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/></svg>';

  function askConfirm(opts, onYes){
    cfmTitle.textContent = opts.title || 'Are you sure?';
    cfmText.textContent  = opts.text  || 'This action cannot be undone.';
    var isDanger = opts.color === 'danger';
    cfmIcon.style.background = isDanger ? 'rgba(212,59,59,.12)' : 'rgba(55,214,122,.12)';
    cfmIcon.style.color      = isDanger ? '#b42f2f' : '#1c7a49';
    if (cfmIconSvg) cfmIconSvg.innerHTML = isDanger ? BAN_SVG : CHECK_SVG;
    cfmOk.style.background   = isDanger ? 'linear-gradient(135deg,#dc2626,#b91c1c)' : 'linear-gradient(135deg,var(--gold-300),var(--gold-500))';
    cfmOk.style.color        = isDanger ? '#fff' : '#2a1a00';
    cfmOk.querySelector('.btn-label').textContent = opts.okLabel || 'Yes, proceed';
    if (typeof setBtnLoading === 'function') setBtnLoading(cfmOk, false);
    cfmOk.disabled = false;

    pendingAction = onYes;
    openModal(cfm);
  }
  cfm.querySelectorAll('[data-cfm-close]').forEach(function(el){
    el.addEventListener('click', function(){ closeModal(cfm); pendingAction = null; });
  });
  cfmOk.addEventListener('click', function(){
    if (!pendingAction) return;
    var fn = pendingAction;
    pendingAction = null;
    // Disable cancel buttons while processing
    cfm.querySelectorAll('[data-cfm-close]').forEach(function(b){ b.disabled = true; });
    // Spin the confirm-ok button
    if (typeof setBtnLoading === 'function') setBtnLoading(cfmOk, true);
    fn();
  });

  // ---- Form submit → confirm → post with MIN 2.2s spinner time ----
  function postForm(form, url){
    var started = performance.now();
    var MIN_MS = 2200;

    var fd = new FormData(form);
    fetch(url, {
      method:'POST', body:fd,
      headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
      credentials:'same-origin'
    })
    .then(function(r){ return r.json().then(function(d){ return {ok:r.ok, d:d}; }); })
    .then(function(res){
      var elapsed = performance.now() - started;
      var wait = Math.max(0, MIN_MS - elapsed);
      setTimeout(function(){
        // Close all modals now that min spinner time has elapsed
        closeModal(apModal); closeModal(rjModal); closeModal(cfm);

        if (res.ok && res.d.ok){
          if (window.toast) window.toast(res.d.message || 'Done', 'success');
          setTimeout(function(){ window.location.reload(); }, 500);
        } else {
          if (typeof setBtnLoading === 'function') setBtnLoading(cfmOk, false);
          cfm.querySelectorAll('[data-cfm-close]').forEach(function(b){ b.disabled = false; });
          if (window.toast) window.toast((res.d && res.d.message) || 'Something went wrong', 'error');
        }
      }, wait);
    })
    .catch(function(e){
      var elapsed = performance.now() - started;
      var wait = Math.max(0, MIN_MS - elapsed);
      setTimeout(function(){
        if (typeof setBtnLoading === 'function') setBtnLoading(cfmOk, false);
        cfm.querySelectorAll('[data-cfm-close]').forEach(function(b){ b.disabled = false; });
        if (window.toast) window.toast(e.message || 'Network error', 'error');
      }, wait);
    });
  }

  document.getElementById('approveForm').addEventListener('submit', function(e){
    e.preventDefault();
    var form = this;
    askConfirm({
      title:'Approve this deposit?',
      text:'This will credit LKR {{ number_format($deposit->amount, 2) }} to ' + @json($deposit->user->name) + '\'s wallet and send them a confirmation email.',
      okLabel:'Yes, approve',
      color:'success'
    }, function(){
      // Close the approve note modal while we process (cfm stays open with spinner)
      closeModal(apModal);
      postForm(form, '{{ route('admin.deposits.approve', $deposit) }}');
    });
  });

  document.getElementById('rejectForm').addEventListener('submit', function(e){
    e.preventDefault();
    var form = this;
    // Validate reason
    var note = form.querySelector('textarea[name=admin_note]').value.trim();
    if (!note){
      if (window.toast) window.toast('Please enter a rejection reason.', 'error');
      return;
    }
    askConfirm({
      title:'Reject this deposit?',
      text:'The customer will be emailed with the reason you provided. No balance will be credited.',
      okLabel:'Yes, reject',
      color:'danger'
    }, function(){
      closeModal(rjModal);
      postForm(form, '{{ route('admin.deposits.reject', $deposit) }}');
    });
  });
})();
</script>
@endif
@endpush
