{{-- Shared Quick Recharge popup (same as Plans & Rates). --}}
<div class="rc-modal" id="rcModal" hidden aria-hidden="true">
  <div class="rc-modal__backdrop" data-rc-close></div>
  <div class="rc-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="rcModalTitle">
    <button type="button" class="rc-modal__close" data-rc-close aria-label="Close">
      <x-icon name="x" :size="18"/>
    </button>

    <div class="rc-modal__head">
      <img src="" alt="" class="rc-modal__logo" id="rcLogo">
      <div>
        <h3 id="rcModalTitle">Quick Recharge</h3>
        <small id="rcOperatorName">—</small>
      </div>
    </div>

    <form class="rc-modal__form" id="rcForm" autocomplete="off">
      @csrf
      <input type="hidden" name="service_id" id="rcServiceId">

      <div class="rc-modal__grid">
        <div class="field">
          <label id="rcAccountLabel">Mobile / Account Number <span class="req">*</span></label>
          <input type="tel" name="account_number" id="rcAccount" placeholder="e.g. 0771234567" required>
        </div>
        <div class="field" id="rcNotifyField" hidden>
          <label>Notify Number <small style="color:var(--muted);font-weight:600;">(optional)</small></label>
          <input type="tel" name="notify_number" id="rcNotify" placeholder="Same as above if blank" disabled>
        </div>
        <div class="field">
          <label id="rcAmountLabel">Amount (LKR) <span class="req">*</span></label>
          <input type="number" name="amount" id="rcAmount" min="10" max="100000" step="0.01" placeholder="e.g. 250" required>
        </div>
      </div>

      <button type="submit" class="btn-admin btn-admin--gold rc-modal__submit" id="rcSubmit">
        <span class="btn-label" id="rcSubmitLabel"><x-icon name="bolt-nav" :size="14"/> Recharge Now</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </form>

    <div class="rc-modal__confirm" id="rcConfirm" hidden>
      <h4 id="rcConfirmTitle">Confirm this reload?</h4>
      <p id="rcConfirmText">Are you sure you want to reload from your wallet?</p>
      <div class="rc-modal__confirm-actions">
        <button type="button" class="btn-admin btn-admin--ghost" id="rcConfirmBack">Go back</button>
        <button type="button" class="btn-admin btn-admin--gold" id="rcConfirmYes">Yes, reload now</button>
      </div>
    </div>

    <div class="rc-modal__plan" id="rcPlanBox">
      <div class="rc-modal__plan-label">Selected plan</div>
      <div class="rc-modal__plan-card">
        <img src="" alt="" id="rcPlanLogo">
        <div class="rc-modal__plan-body">
          <b id="rcPlanPrice">—</b>
          <span id="rcPlanName">—</span>
          <small id="rcPlanValidity"></small>
        </div>
        <span class="cb-badge" id="rcPlanCb" style="display:none;"></span>
      </div>
      <ul class="rc-modal__plan-details" id="rcPlanDetails"></ul>
    </div>

    <div class="rc-modal__hint" id="rcHint" hidden>
      <div class="rc-modal__hint-ic" id="rcHintIc"></div>
      <p id="rcHintText"></p>
    </div>

    <div class="rc-modal__generating" id="rcGenerating" hidden>
      <div class="rc-modal__success-icon rc-modal__success-icon--pending"><x-icon name="clock" :size="28"/></div>
      <h4>Generating your receipt…</h4>
      <p>Please wait while we prepare your receipt.</p>
    </div>

    <div class="rc-modal__success" id="rcSuccess" hidden>
      <div class="rc-modal__success-icon" id="rcSuccessIcon"><x-icon name="check" :size="28"/></div>
      <h4 id="rcSuccessTitle">Recharge Successful!</h4>
      <p id="rcSuccessMsg"></p>
      <div class="rc-modal__success-actions" id="rcSuccessActions">
        <a href="#" class="btn-admin btn-admin--ghost btn-admin--sm" id="rcViewOrder" target="_blank">View Receipt</a>
        <a href="#" class="btn-admin btn-admin--gold btn-admin--sm" id="rcDownload" target="_blank" hidden data-download><x-icon name="download" :size="13"/> Download</a>
        <button type="button" class="btn-admin btn-admin--primary btn-admin--sm" data-rc-close>Done</button>
      </div>
    </div>
  </div>
</div>

@once
<style>
[hidden]{display:none !important;}
.rc-modal{
  position:fixed; inset:0; z-index:9999;
  display:flex; align-items:center; justify-content:center;
  padding:20px;
  overflow-y:auto; overscroll-behavior:contain;
  -webkit-overflow-scrolling:touch;
}
.rc-modal[hidden]{display:none !important;}
.rc-modal__backdrop{
  position:absolute; inset:0;
  background:rgba(7,27,61,.62);
  animation:rcFade .22s ease;
}
@keyframes rcFade{from{opacity:0}to{opacity:1}}
.rc-modal__dialog{
  position:relative; width:100%; max-width:440px;
  background:#fff; border-radius:20px;
  box-shadow:0 30px 80px rgba(7,27,61,.35);
  padding:22px 22px 20px;
  animation:rcPop .28s cubic-bezier(.2,.9,.3,1.2);
  max-height:calc(100vh - 40px); overflow-y:auto;
}
@keyframes rcPop{
  from{opacity:0; transform:translateY(20px) scale(.96);}
  to{opacity:1; transform:none;}
}
.rc-modal__close{
  position:absolute; top:12px; right:12px;
  width:34px; height:34px; border-radius:50%;
  border:0; background:rgba(11,42,91,.06);
  color:var(--navy-700); cursor:pointer;
  display:inline-flex; align-items:center; justify-content:center;
  transition:.18s;
}
.rc-modal__close:hover{background:rgba(212,59,59,.12); color:#b42f2f;}
.rc-modal__head{
  display:flex; align-items:center; gap:12px;
  padding-bottom:16px; margin-bottom:16px;
  border-bottom:1px dashed var(--line);
}
.rc-modal__logo{
  width:52px; height:52px; object-fit:contain;
  padding:6px; border:1px solid var(--line); border-radius:12px; background:#fff;
  box-shadow:0 2px 6px rgba(7,27,61,.06);
}
.rc-modal__head h3{
  margin:0; font-size:18px; font-weight:800; color:var(--navy-900);
  letter-spacing:-.01em;
}
.rc-modal__head small{
  font-size:12px; font-weight:700; color:var(--gold-600);
  text-transform:uppercase; letter-spacing:.08em;
}
.rc-modal__grid{display:grid; gap:12px; margin-bottom:14px;}
.rc-modal__submit{width:100%; height:46px; font-size:14.5px;}
.rc-modal__plan{margin-top:16px;}
.rc-modal__plan-label{
  font-size:11px; font-weight:800; letter-spacing:.12em;
  text-transform:uppercase; color:var(--muted); margin-bottom:8px;
}
.rc-modal__plan-card{
  display:flex; align-items:center; gap:12px;
  padding:12px 14px;
  background:linear-gradient(135deg,#fff9ec,#fff);
  border:1px solid rgba(232,163,23,.3); border-radius:14px;
}
.rc-modal__plan-card img{
  width:42px; height:42px; object-fit:contain; flex:none;
  padding:4px; background:#fff; border:1px solid var(--line); border-radius:10px;
}
.rc-modal__plan-body{flex:1; min-width:0; display:flex; flex-direction:column; gap:2px;}
.rc-modal__plan-body b{font-size:18px; font-weight:800; color:var(--navy-900);}
.rc-modal__plan-body span{
  font-size:13px; font-weight:600; color:var(--navy-700);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.rc-modal__plan-body small{
  font-size:11.5px; font-weight:700; color:var(--muted);
  display:inline-flex; align-items:center; gap:4px;
}
.rc-modal__plan-details{
  list-style:none; margin:12px 0 0; padding:0;
  border-top:1px dashed var(--line);
}
.rc-modal.is-success .rc-modal__form,
.rc-modal.is-success .rc-modal__plan,
.rc-modal.is-success .rc-modal__hint{display:none;}
.rc-modal.is-generating .rc-modal__form,
.rc-modal.is-generating .rc-modal__plan,
.rc-modal.is-generating .rc-modal__hint,
.rc-modal.is-generating .rc-modal__success{display:none;}
.rc-modal__generating,
.rc-modal__success{
  text-align:center; padding:10px 0 4px;
  display:flex; flex-direction:column; align-items:center; gap:10px;
}
.rc-modal__generating p,
.rc-modal__success p{margin:0; font-size:13.5px; color:var(--muted); font-weight:600; line-height:1.5;}
.rc-modal__generating h4,
.rc-modal__success h4{margin:4px 0 0; font-size:20px; color:var(--navy-900);}
.rc-modal__success-icon{
  width:64px; height:64px; border-radius:50%;
  background:linear-gradient(135deg,#22c55e,#16a34a);
  color:#fff; display:inline-flex; align-items:center; justify-content:center;
  box-shadow:0 10px 24px rgba(34,197,94,.35);
  animation:rcPop .4s cubic-bezier(.2,.9,.3,1.3);
}
.rc-modal__success-icon--pending{
  background:linear-gradient(135deg,var(--gold-300),var(--gold-500));
  color:#2a1a00;
  box-shadow:0 10px 24px rgba(232,163,23,.35);
  animation:rcSpin 1.2s linear infinite, rcPop .4s cubic-bezier(.2,.9,.3,1.3);
}
@keyframes rcSpin{ to{transform:rotate(360deg);} }
.rc-modal__success-actions{display:flex; gap:10px; margin-top:10px; flex-wrap:wrap; justify-content:center;}
.rc-modal__hint{
  margin-top:16px; padding:14px 16px; border-radius:14px;
  background:linear-gradient(135deg,#fff9ec,#fff);
  border:1px solid rgba(232,163,23,.3);
  display:flex; align-items:flex-start; gap:12px;
}
.rc-modal__hint-ic{
  width:32px; height:32px; border-radius:10px; flex:none;
  background:linear-gradient(135deg,var(--gold-300),var(--gold-500));
  color:#2a1a00; display:inline-flex; align-items:center; justify-content:center;
}
.rc-modal__hint p{margin:0; font-size:13px; font-weight:600; color:var(--navy-800); line-height:1.5;}
.rc-modal__form input[readonly]{background:#f7f9fd; color:var(--navy-800); font-weight:700; cursor:default;}
.rc-modal__confirm{text-align:center; padding:8px 4px 2px;}
.rc-modal__confirm h4{margin:0 0 8px; font-size:18px; font-weight:800; color:var(--navy-900);}
.rc-modal__confirm p{margin:0 0 16px; font-size:14px; font-weight:600; color:var(--navy-800); line-height:1.55;}
.rc-modal__confirm-actions{display:flex; gap:10px; justify-content:center; flex-wrap:wrap;}
.rc-modal.is-confirming .rc-modal__form,
.rc-modal.is-confirming .rc-modal__plan,
.rc-modal.is-confirming .rc-modal__hint{display:none;}
button.service-card{
  font:inherit; color:inherit; text-align:center;
  width:100%; -webkit-appearance:none; appearance:none;
}
@media (max-width:580px){
  .rc-modal{padding:12px;}
  .rc-modal__dialog{border-radius:18px; padding:18px 16px 16px; max-height:92vh;}
  .rc-modal__logo{width:44px; height:44px;}
  .rc-modal__head h3{font-size:16px;}
  .rc-modal__submit{height:48px; font-size:14px;}
}
</style>
<script>
(function(){
  var modal = document.getElementById('rcModal');
  if (!modal || window.__hprRcModalReady) return;
  window.__hprRcModalReady = true;
  document.body.appendChild(modal);

  @php
    $hprUser = auth()->user();
    $hprWallet = $hprUser
      ? ($hprUser->wallet ?: \App\Models\Wallet::firstOrCreate(['user_id' => $hprUser->id]))
      : null;
    $hprMin = \App\Support\WalletLimits::minBalance();
    $hprBal = (float) ($hprWallet->balance ?? 0);
    $hprCan = $hprUser && $hprWallet
      ? \App\Support\WalletLimits::canStartRecharge($hprUser, $hprWallet)
      : false;
    $hprBlock = $hprCan
      ? ''
      : ($hprUser && $hprWallet
          ? \App\Support\WalletLimits::blockMessage($hprUser, $hprWallet)
          : ('Add at least LKR ' . number_format($hprMin, 2) . ' to your wallet before you can recharge.'));
  @endphp
  window.__hprWallet = {
    can_recharge: {{ $hprCan ? 'true' : 'false' }},
    message: @json($hprBlock)
  };

  var mLogo    = document.getElementById('rcLogo');
  var mOpName  = document.getElementById('rcOperatorName');
  var mForm    = document.getElementById('rcForm');
  var mSvcId   = document.getElementById('rcServiceId');
  var mAcc     = document.getElementById('rcAccount');
  var mNotify  = document.getElementById('rcNotify');
  var mNotifyField = document.getElementById('rcNotifyField');
  var mConfirm = document.getElementById('rcConfirm');
  var mConfirmText = document.getElementById('rcConfirmText');
  var mConfirmBack = document.getElementById('rcConfirmBack');
  var mConfirmYes = document.getElementById('rcConfirmYes');
  var currentMode = 'reload';
  var currentFee = 0;         // flat LKR fee (when fee type is FLAT)
  var currentFeePct = 0;      // percent fee (when fee type is PCT)
  var mAmount  = document.getElementById('rcAmount');
  var mSubmit  = document.getElementById('rcSubmit');
  var mSpinner = mSubmit.querySelector('.btn-spinner');
  var mLabel   = mSubmit.querySelector('.btn-label');
  var mPlanLogo= document.getElementById('rcPlanLogo');
  var mPlanName= document.getElementById('rcPlanName');
  var mPlanPr  = document.getElementById('rcPlanPrice');
  var mPlanVal = document.getElementById('rcPlanValidity');
  var mPlanCb  = document.getElementById('rcPlanCb');
  var mPlanDetails = document.getElementById('rcPlanDetails');
  var mSuccess = document.getElementById('rcSuccess');
  var mGenerating = document.getElementById('rcGenerating');
  var mSuccessIcon = document.getElementById('rcSuccessIcon');
  var mSuccessTitle = document.getElementById('rcSuccessTitle');
  var mSuccessMsg = document.getElementById('rcSuccessMsg');
  var mViewOrder  = document.getElementById('rcViewOrder');
  var mDownload   = document.getElementById('rcDownload');
  var mTitle = document.getElementById('rcModalTitle');
  var mPlanBox = document.getElementById('rcPlanBox');
  var mHint    = document.getElementById('rcHint');
  var mHintIc  = document.getElementById('rcHintIc');
  var mHintText= document.getElementById('rcHintText');
  var mAccLabel= document.getElementById('rcAccountLabel');
  var mAmountLabel= document.getElementById('rcAmountLabel');
  var mSubmitLabel = document.getElementById('rcSubmitLabel');
  var mAccountInput = document.getElementById('rcAccount');
  var mAmountInput  = document.getElementById('rcAmount');

  var iconSvg = {
    bolt: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    bill: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>'
  };

  function lockBodyScroll(){
    window.addEventListener('wheel', onScrollAttempt, {capture:true, passive:false});
    window.addEventListener('touchmove', onScrollAttempt, {capture:true, passive:false});
    window.addEventListener('keydown', onKeyScrollAttempt, {capture:true});
  }
  function unlockBodyScroll(){
    window.removeEventListener('wheel', onScrollAttempt, {capture:true, passive:false});
    window.removeEventListener('touchmove', onScrollAttempt, {capture:true, passive:false});
    window.removeEventListener('keydown', onKeyScrollAttempt, {capture:true});
  }
  function isScrollableSurface(target){
    var d = modal.querySelector('.rc-modal__dialog');
    if (!d) return false;
    return target === modal || target === d || d.contains(target);
  }
  function onScrollAttempt(e){
    if (isScrollableSurface(e.target)) return;
    e.preventDefault();
  }
  function onKeyScrollAttempt(e){
    var keys = [32,33,34,35,36,38,40];
    if (keys.indexOf(e.keyCode) === -1) return;
    if (isScrollableSurface(e.target)) return;
    e.preventDefault();
  }

  function openModal(card){
    var svcId  = card.dataset.serviceId;
    var amount = card.dataset.amount || '';
    var name   = card.dataset.name || '';
    var val    = card.dataset.validity || '';
    var logo   = card.dataset.logo;
    var op     = card.dataset.opName;
    var cb     = card.dataset.cb;
    var mode   = card.dataset.mode || 'reload';
    currentMode = mode;
    // Customer service fee (surcharge) for bill services with negative profit.
    currentFee = parseFloat(card.dataset.feeFlat || '0') || 0;
    currentFeePct = parseFloat(card.dataset.feePct || '0') || 0;
    var hideNotify = card.dataset.hideNotify === '1'
      || (card.dataset.category || '') === 'mobile';
    var details;
    try { details = JSON.parse(card.dataset.details || '[]'); } catch(e){ details = []; }

    mSvcId.value  = svcId;
    mAcc.value = '';
    mNotify.value = '';
    if (mNotify){
      mNotify.disabled = hideNotify;
      mNotify.value = '';
    }
    if (mNotifyField){
      mNotifyField.hidden = hideNotify;
      mNotifyField.style.display = hideNotify ? 'none' : '';
    }
    if (mConfirm) mConfirm.hidden = true;
    modal.classList.remove('is-confirming');
    if (mPlanLogo) mPlanLogo.src = logo;
    mLogo.src = logo;
    mOpName.textContent = op;

    if (mode === 'plan'){
      mTitle.textContent = 'Recharge — ' + op;
      mAccLabel.innerHTML = 'Mobile / Account Number <span class="req">*</span>';
      mAmountLabel.innerHTML = 'Amount (LKR) <span class="req">*</span>';
      mAccountInput.placeholder = 'e.g. 0771234567';
      mAmountInput.placeholder = '';
      mAmountInput.readOnly = true;
      mAmountInput.min = '50';
      mSubmitLabel.innerHTML = iconSvg.bolt + ' Recharge Now';
      mAmount.value = amount;
      mPlanName.textContent = name;
      mPlanPr.textContent = 'LKR ' + Number(amount).toLocaleString('en-LK', {minimumFractionDigits:0, maximumFractionDigits:2});
      if (val){ mPlanVal.textContent = val; mPlanVal.style.display=''; } else { mPlanVal.textContent=''; mPlanVal.style.display='none'; }
      if (cb && parseFloat(cb) > 0){ mPlanCb.style.display = ''; mPlanCb.textContent = '+LKR ' + cb; }
      else { mPlanCb.style.display = 'none'; }
      mPlanDetails.innerHTML = '';
      mPlanDetails.style.display = 'none';
      mPlanBox.style.display = '';
      mHint.hidden = true;
    } else if (mode === 'reload'){
      mTitle.textContent = 'Recharge — ' + op;
      mAccLabel.innerHTML = 'Mobile / Account Number <span class="req">*</span>';
      mAmountLabel.innerHTML = 'Amount (LKR) <span class="req">*</span>';
      mAccountInput.placeholder = 'e.g. 0771234567';
      mAmountInput.placeholder = 'Enter amount (e.g. 250)';
      mAmountInput.readOnly = false;
      mAmountInput.min = '50';
      mAmountInput.value = '';
      mSubmitLabel.innerHTML = iconSvg.bolt + ' Recharge Now';
      mPlanBox.style.display = 'none';
      mHintIc.innerHTML = iconSvg.bolt;
      mHintText.textContent = 'Minimum recharge is LKR 50. Enter the amount to send. You pay exactly this amount.';
      mHint.hidden = false;
    } else {
      mTitle.textContent = op;
      mAccLabel.innerHTML = 'Account / Reference Number <span class="req">*</span>';
      mAmountLabel.innerHTML = 'Bill Amount (LKR) <span class="req">*</span>';
      mAccountInput.placeholder = 'e.g. account number';
      mAmountInput.placeholder = 'Enter exact bill amount';
      mAmountInput.readOnly = false;
      mAmountInput.min = '10';
      mAmountInput.value = '';
      mSubmitLabel.innerHTML = iconSvg.bill + ' Pay Bill Now';
      mPlanBox.style.display = 'none';
      mHintIc.innerHTML = iconSvg.bill;
      mHintText.textContent = 'Minimum bill payment is LKR 10. Enter the exact amount due and your account / reference number.';
      mHint.hidden = false;
    }

    // Per-service field labels: DTH needs a smart-card number, insurance a
    // policy number, electricity a CEB/LECO account number, etc. These come
    // from the card's data attributes and override the mode defaults above.
    var accLabel = card.dataset.accLabel || '';
    var accPlaceholder = card.dataset.accPlaceholder || '';
    var accHint = card.dataset.accHint || '';
    if (accLabel){
      mAccLabel.innerHTML = accLabel + ' <span class="req">*</span>';
    }
    if (accPlaceholder){
      mAccountInput.placeholder = accPlaceholder;
    }
    if (accHint && mode !== 'plan' && !mHint.hidden){
      mHintText.textContent = accHint;
    }

    modal.classList.remove('is-success', 'is-generating');
    mSuccess.hidden = true;
    mGenerating.hidden = true;
    mForm.style.display = '';
    mSubmit.disabled = false;
    mSubmit.classList.remove('is-loading');
    mSpinner.hidden = true;
    mLabel.hidden = false;
    if (mSuccessIcon){
      mSuccessIcon.className = 'rc-modal__success-icon';
      mSuccessIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
    }
    if (mSuccessTitle) mSuccessTitle.textContent = 'Recharge Successful!';
    if (mViewOrder) mViewOrder.textContent = 'View Order';
    lockBodyScroll();
    modal.hidden = false;
    modal.setAttribute('aria-hidden','false');
    setTimeout(function(){ mAcc.focus(); }, 80);
  }

  function closeModal(){
    modal.hidden = true;
    modal.setAttribute('aria-hidden','true');
    modal.classList.remove('is-success', 'is-generating', 'is-confirming');
    if (mConfirm) mConfirm.hidden = true;
    unlockBodyScroll();
    mSubmit.disabled = false;
    mSubmit.classList.remove('is-loading');
    mSpinner.hidden = true; mLabel.hidden = false;
    mSuccess.hidden = true;
    mGenerating.hidden = true;
    mForm.style.display = '';
    mPlanBox.style.display = '';
    mHint.hidden = true;
    mAmountInput.readOnly = false;
  }

  document.addEventListener('click', function(e){
    if (e.target.closest('[data-rc-close]')) { closeModal(); return; }
    var card = e.target.closest('[data-plan-card], [data-rc-custom]');
    if (!card || modal.contains(card)) return;
    e.preventDefault();
    if (window.__hprWallet && window.__hprWallet.can_recharge === false) {
      var msg = window.__hprWallet.message || 'Add money to your wallet before you can recharge.';
      if (window.toast) window.toast(msg, 'error');
      else alert(msg);
      return;
    }
    openModal(card);
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });

  function hideConfirm(){
    if (mConfirm) mConfirm.hidden = true;
    modal.classList.remove('is-confirming');
    mForm.style.display = '';
    if (currentMode === 'plan' && mPlanBox) mPlanBox.style.display = '';
    else if (currentMode !== 'plan' && mHint) mHint.hidden = false;
  }
  function feeForAmount(amt){
    if (currentFeePct > 0) return Math.round(amt * currentFeePct) / 100;
    if (currentFee > 0) return currentFee;
    return 0;
  }

  function showOrderConfirm(){
    var amt = parseFloat(mAmount.value || '0');
    var acc = (mAcc.value || '').trim();
    var op = (mOpName.textContent || '').trim();
    var isBill = currentMode === 'bill';
    var fee = feeForAmount(amt);
    var title = document.getElementById('rcConfirmTitle') || document.querySelector('#rcConfirm h4');
    if (title) title.textContent = isBill ? 'Confirm this payment?' : 'Confirm this reload?';
    if (mConfirmText){
      if (fee > 0){
        mConfirmText.innerHTML = 'Pay for ' + (op || 'this service') + ' to <b>' + acc + '</b>:<br>'
          + 'Bill amount: LKR ' + amt.toFixed(2) + '<br>'
          + 'Service fee: LKR ' + fee.toFixed(2) + '<br>'
          + '<b>Total from wallet: LKR ' + (amt + fee).toFixed(2) + '</b>';
      } else {
        mConfirmText.textContent = (isBill ? 'Are you sure you want to pay' : 'Are you sure you want to reload')
          + ' LKR ' + amt.toFixed(2) + ' to ' + acc + (op ? (' for ' + op) : '') + ' from your wallet?';
      }
    }
    if (mConfirmYes) mConfirmYes.textContent = isBill ? 'Yes, pay now' : 'Yes, reload now';
    mForm.style.display = 'none';
    if (mPlanBox) mPlanBox.style.display = 'none';
    if (mHint) mHint.hidden = true;
    if (mConfirm) mConfirm.hidden = false;
    modal.classList.add('is-confirming');
  }
  if (mConfirmBack){
    mConfirmBack.addEventListener('click', hideConfirm);
  }
  if (mConfirmYes){
    mConfirmYes.addEventListener('click', function(){
      hideConfirm();
      sendOrder();
    });
  }

  mForm.addEventListener('submit', function(e){
    e.preventDefault();
    if (mSubmit.disabled) return;
    if (!mForm.reportValidity()) return;
    showOrderConfirm();
  });

  function sendOrder(){
    var fd = new FormData(mForm);
    var started = performance.now();
    var MIN_SPIN = 2200;
    if (typeof window.setBtnLoading === 'function') window.setBtnLoading(mSubmit, true);
    else { mSubmit.disabled = true; mSubmit.classList.add('is-loading'); mSpinner.hidden = false; }

    function stopBtnSpinner(cb){
      var wait = Math.max(0, MIN_SPIN - (performance.now() - started));
      setTimeout(function(){
        if (typeof window.setBtnLoading === 'function') window.setBtnLoading(mSubmit, false);
        else { mSubmit.disabled = false; mSubmit.classList.remove('is-loading'); mSpinner.hidden = true; }
        if (cb) cb();
      }, wait);
    }

    // POST the order. If the session/CSRF token has expired (HTTP 419) we
    // silently fetch a fresh token and retry ONCE, so the customer never sees
    // a raw "CSRF token mismatch" page or has to log out and back in.
    function postOrder(isRetry){
      return fetch('{{ route('recharge.confirm') }}', {
        method: 'POST',
        body: fd,
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept':'application/json'},
        credentials: 'same-origin'
      }).then(function(r){
        if (r.status === 419 && !isRetry){
          // Expired token — get a new one, patch the form + FormData, retry.
          return fetch('{{ route('csrf.token') }}', {
            headers: {'Accept':'application/json'}, credentials: 'same-origin'
          })
          .then(function(t){ return t.json(); })
          .then(function(tj){
            if (tj && tj.token){
              var tokenField = mForm.querySelector('input[name=_token]');
              if (tokenField) tokenField.value = tj.token;
              fd.set('_token', tj.token);
            }
            return postOrder(true);
          });
        }
        return r.json().then(function(d){ return {ok:r.ok, data:d, status:r.status}; });
      });
    }

    postOrder(false)
    .then(function(res){
      if (res.status === 419){
        throw new Error('Your session expired. Please refresh the page and try again.');
      }
      if (!res.ok || !res.data.ok){
        throw new Error((res.data && res.data.message) || 'Order failed. Please try again.');
      }
      var o = res.data.order;
      var isSuccess = res.data.status === 'success';
      var hasInvoice = !!res.data.has_invoice;

      stopBtnSpinner(function(){
        if (isSuccess && hasInvoice){
          mForm.style.display = 'none';
          mPlanBox.style.display = 'none';
          mHint.hidden = true;
          mSuccess.hidden = true;
          mGenerating.hidden = false;
          modal.classList.add('is-generating');
          if (window.toast) window.toast(res.data.message || 'Recharge successful!', 'success');
          setTimeout(function(){
            mSuccessTitle.textContent = 'Recharge Successful!';
            mSuccessMsg.textContent = res.data.message || ('Payment of LKR ' + Number(o.amount).toFixed(2) + ' to ' + o.account + ' completed.');
            mViewOrder.href = res.data.invoice_url || o.redirect;
            mViewOrder.textContent = 'View Receipt';
            if (res.data.download_url){ mDownload.href = res.data.download_url; mDownload.hidden = false; }
            else mDownload.hidden = true;
            mGenerating.hidden = true;
            mSuccess.hidden = false;
            modal.classList.remove('is-generating');
            modal.classList.add('is-success');
          }, 1200);
        } else if (isSuccess){
          mSuccessTitle.textContent = 'Recharge Successful!';
          mSuccessMsg.textContent = res.data.message || ('Payment of LKR ' + Number(o.amount).toFixed(2) + ' to ' + o.account + ' completed.');
          mViewOrder.href = o.redirect;
          mViewOrder.textContent = 'View Order';
          mDownload.hidden = true;
          mForm.style.display = 'none';
          mPlanBox.style.display = 'none';
          mHint.hidden = true;
          mSuccess.hidden = false;
          modal.classList.add('is-success');
          if (window.toast) window.toast(res.data.message || 'Recharge successful!', 'success');
        } else {
          mSuccessIcon.className = 'rc-modal__success-icon rc-modal__success-icon--pending';
          mSuccessTitle.textContent = 'Recharge is Processing';
          mSuccessMsg.textContent = res.data.message || ('Your recharge of LKR ' + Number(o.amount).toFixed(2) + ' to ' + o.account + ' is being processed.');
          mViewOrder.href = o.redirect;
          mViewOrder.textContent = 'Track Order Status';
          mDownload.hidden = true;
          mForm.style.display = 'none';
          mPlanBox.style.display = 'none';
          mHint.hidden = true;
          mSuccess.hidden = false;
          modal.classList.add('is-success');
          if (window.toast) window.toast(res.data.message || 'Recharge is processing…', 'info');
        }
      });
    })
    .catch(function(err){
      stopBtnSpinner(function(){
        if (window.toast) window.toast(err.message || 'Something went wrong.', 'error');
        else alert(err.message || 'Something went wrong.');
      });
    });
  }
})();
</script>
@endonce
