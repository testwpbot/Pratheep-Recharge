<!DOCTYPE html>
<html lang="en" class="is-loading">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'My Dashboard') — {{ config('app.name') }}</title>
@include('partials.favicon')
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
<link rel="stylesheet" href="{{ asset('css/loader.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@stack('styles')
<style>
/* Mobile-only tighter first screen for dashboard pages.
   Plans & Rates does not set dash_compact, so it stays as-is. */
@media (max-width:720px){
  [data-dash-compact] .app-content{padding:12px;}
  [data-dash-compact] .app-topbar{height:54px;}
  [data-dash-compact] .app-topbar h2{font-size:15px;}
  [data-dash-compact] .card{padding:14px 12px; border-radius:14px;}
  [data-dash-compact] .card + .card{margin-top:12px;}
  [data-dash-compact] .card__head{margin-bottom:12px; gap:8px;}
  [data-dash-compact] .card__head h3{font-size:15.5px;}
  [data-dash-compact] .stats-grid{
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:8px; margin-bottom:12px;
  }
  [data-dash-compact] .stat{padding:10px 10px; border-radius:12px;}
  [data-dash-compact] .stat b{font-size:16px;}
  [data-dash-compact] .stat span{font-size:10px;}
  [data-dash-compact] .alert{margin-bottom:12px; padding:10px 12px;}
  [data-dash-compact] .wallet-notice{padding:10px 12px; margin-bottom:12px; border-radius:12px;}
}
/* Customer account menu on phones: no empty stretch before Sign Out. */
@media (max-width:900px){
  body[data-shell="app"] .app-sidebar{
    height:100vh;
    max-height:100vh;
    height:100dvh;
    max-height:100dvh;
    overflow:hidden;
    padding-bottom:0 !important;
  }
  body[data-shell="app"] .app-sidebar nav{
    flex:none !important;
    padding:8px 10px 4px;
  }
  body[data-shell="app"] .app-sidebar nav a{padding:9px 12px;}
  body[data-shell="app"] .app-sidebar__brand{padding:16px 18px;}
  body[data-shell="app"] .app-sidebar__footer{
    margin-top:0 !important;
    padding:10px 16px calc(10px + env(safe-area-inset-bottom, 0px));
  }
}
</style>
</head>
<body data-shell="app"@if(trim($__env->yieldContent('dash_compact'))) data-dash-compact @endif>

@include('partials.loader')

<div class="app-shell">
  {{-- Mobile scrim --}}
  <div class="sidebar-scrim" id="sidebarScrim"></div>

  <aside class="app-sidebar" id="sidebar">
    <div class="app-sidebar__brand">
      <img src="{{ asset('assets/logo-mark.png') }}" alt="">
      <div>
        <h1>My Account</h1>
        <small>Happy Pratheep</small>
      </div>
      <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu">&times;</button>
    </div>

    <nav>
      <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <x-icon name="home" :size="18"/> Dashboard
      </a>
      <a href="{{ route('dashboard.plans') }}" class="{{ request()->routeIs('dashboard.plans') ? 'active' : '' }}">
        <x-icon name="tag" :size="18"/> Plans & Rates
      </a>
      <a href="{{ route('recharge.history') }}" class="{{ request()->routeIs('recharge.history', 'recharge.show', 'recharge.invoice') ? 'active' : '' }}">
        <x-icon name="bill" :size="18"/> My Recharges
      </a>
      <a href="{{ route('complaints') }}" class="{{ request()->routeIs('complaints*') ? 'active' : '' }}">
        <x-icon name="alert" :size="18"/> Complaints
        @php $openComplaints = \App\Models\Complaint::where('user_id', auth()->id())->whereIn('status', ['open','in_progress'])->count(); @endphp
        @if($openComplaints) <em class="sb-badge">{{ $openComplaints }}</em> @endif
      </a>
      <a href="{{ route('earnings') }}" class="{{ request()->routeIs('earnings*') ? 'active' : '' }}">
        <x-icon name="gift-dr" :size="18"/> Earnings
      </a>
      <a href="{{ route('refunds') }}" class="{{ request()->routeIs('refunds*') ? 'active' : '' }}">
        <x-icon name="check-circle" :size="18"/> Refunds
      </a>
      <a href="{{ route('wallet') }}" class="{{ request()->routeIs('wallet*') ? 'active' : '' }}">
        <x-icon name="wallet" :size="18"/> My Wallet
      </a>
      <a href="{{ route('home') }}" target="_blank">
        <x-icon name="pin" :size="18"/> Visit Home
      </a>
    </nav>

    <div class="app-sidebar__footer">
      Logged in as<br><b style="color:#fff">{{ auth()->user()->name }}</b>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" data-loading="Signing out…">
          <span class="btn-label">Sign Out</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </form>
    </div>
  </aside>

  <div class="app-main">
    <div class="app-topbar">
      <button type="button" class="topbar-burger" id="sidebarToggle" aria-label="Open menu">
        <span></span><span></span><span></span>
      </button>
      <h2>@yield('title', 'Dashboard')</h2>
      <div class="app-topbar__user">
        <img src="{{ auth()->user()->avatarUrl(40) }}" alt="{{ auth()->user()->name }}">
        <span>{{ auth()->user()->name }}</span>
      </div>
    </div>

    <div class="app-content">
      @if (!empty($walletNotice) && !request()->routeIs('wallet'))
        <div class="wallet-notice wallet-notice--{{ $walletNotice['type'] }}">
          <div class="wallet-notice__text">
            <b>{{ $walletNotice['title'] }}</b>
            <p>{{ $walletNotice['message'] }}</p>
          </div>
          <a href="{{ route('wallet') }}" class="btn-admin btn-admin--gold btn-admin--sm">Add money</a>
        </div>
      @endif
      @if (session('status') || session('success'))
        <div class="alert alert--success">{{ session('status') ?: session('success') }}</div>
      @endif
      @if (session('error'))
        <div class="alert alert--error">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert alert--error">
          <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
          </ul>
        </div>
      @endif

      @yield('content')
    </div>
  </div>
</div>

<script src="{{ asset('js/landing.js') }}"></script>
@include('partials.hpr-dd')
@include('partials.whatsapp-float')
@include('partials.dashboard-alerts')
@stack('scripts')

{{-- Global Download Receipt modal --}}
<div class="rc-modal" id="downloadModal" hidden aria-hidden="true">
  <div class="rc-modal__backdrop" id="dlBackdrop"></div>
  <div class="rc-modal__dialog" style="max-width:360px; text-align:center;" role="dialog" aria-modal="true" aria-labelledby="dlTitle">
    <div id="dlBody" style="padding:34px 16px 24px; display:flex; flex-direction:column; align-items:center; gap:14px;">
      <div id="dlIconWrap" style="width:80px; height:80px; display:grid; place-items:center;">
        <div class="dl-spinner" id="dlSpinner"></div>
      </div>
      <h4 id="dlTitle" style="margin:4px 0 0; font-size:19px; color:var(--navy-900);">Preparing your receipt…</h4>
      <p id="dlMsg" style="margin:0; font-size:13.5px; color:var(--muted); font-weight:600; line-height:1.5;">Please wait while we get your receipt ready.</p>
    </div>
  </div>
</div>

<style>
.dl-spinner{
  width:56px;
  height:56px;
  border:4px solid rgba(232,163,23,.22);
  border-top-color:var(--gold-500, #E8A317);
  border-right-color:var(--gold-500, #E8A317);
  border-radius:50%;
  animation:dlSpin .8s linear infinite;
}
@keyframes dlSpin{to{transform:rotate(360deg);}}
</style>

<script>
/* Global Download Receipt popup
 * Click <a data-download href="...">:
 *   1. Open modal with plain gold spinner + "Preparing your receipt…"
 *   2. Fetch the PNG as a Blob in the background (actual download work)
 *   3. When Blob is ready → swap to green verified-badge + success text
 *   4. ONLY THEN trigger browser save dialog (click a hidden a[download])
 *   5. Auto-close ~1s later. */
(function(){
  var modal = document.getElementById('downloadModal');
  if (!modal) return;
  var iconWrap = document.getElementById('dlIconWrap');
  var title = document.getElementById('dlTitle');
  var msg   = document.getElementById('dlMsg');
  var backdrop = document.getElementById('dlBackdrop');
  var closeTimer = null;
  var activeObjectUrl = null;

  var spinnerHtml = '<div class="dl-spinner" id="dlSpinner"></div>';
  var badgeHtml = '<img src="{{ asset('assets/check-badge.png') }}" alt="" width="80" height="80" style="animation:rcPop .4s cubic-bezier(.2,.9,.3,1.3);">';

  function showSpinner(){
    iconWrap.innerHTML = spinnerHtml;
    title.textContent = 'Preparing your receipt…';
    msg.textContent = 'Please wait while we get your receipt ready.';
  }
  function showSuccessAndDownload(blob, filename){
    iconWrap.innerHTML = badgeHtml;
    title.textContent = 'Receipt downloaded!';
    msg.textContent = 'Your receipt has been saved to your device.';

    // Trigger the actual browser download NOW (after success UI is shown)
    if (activeObjectUrl) URL.revokeObjectURL(activeObjectUrl);
    activeObjectUrl = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = activeObjectUrl;
    a.download = filename || 'receipt.png';
    document.body.appendChild(a);
    a.click();
    setTimeout(function(){
      a.remove();
      URL.revokeObjectURL(activeObjectUrl);
      activeObjectUrl = null;
    }, 5000);

    clearTimeout(closeTimer);
    closeTimer = setTimeout(close, 1100);
  }
  function showError(){
    iconWrap.innerHTML = '';
    title.textContent = 'Download failed';
    msg.textContent = 'Something went wrong. Please try again.';
    clearTimeout(closeTimer);
    closeTimer = setTimeout(close, 1800);
  }
  function open(){
    modal.hidden = false;
    modal.setAttribute('aria-hidden','false');
    showSpinner();
  }
  function close(){
    modal.hidden = true;
    modal.setAttribute('aria-hidden','true');
    clearTimeout(closeTimer);
  }

  backdrop.addEventListener('click', close);
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && !modal.hidden) close();
  });

  document.addEventListener('click', function(e){
    var a = e.target.closest('a[data-download]');
    if (!a) return;
    e.preventDefault();
    var url = a.href;
    if (!url) return;
    open();

    // Fetch the file as a blob so we know when it's actually ready,
    // then show success and only then trigger the browser download.
    fetch(url, {credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'image/png,*/*'}})
      .then(function(res){
        if (!res.ok) throw new Error('Network error ' + res.status);
        // Try to read filename from Content-Disposition header
        var cd = res.headers.get('Content-Disposition') || '';
        var m = /filename\*?=(?:UTF-8'')?["']?([^"';\r\n]+)/i.exec(cd);
        var filename = m ? decodeURIComponent(m[1].replace(/^"|"$/g,'')) : (a.getAttribute('data-filename') || 'receipt.png');
        return res.blob().then(function(blob){ return {blob:blob, filename:filename}; });
      })
      .then(function(res){ showSuccessAndDownload(res.blob, res.filename); })
      .catch(function(err){
        console.warn('Download fetch failed, falling back to direct navigation:', err);
        // Fallback: navigate directly so user still gets the file
        window.location.href = url;
        showError();
      });
  });
})();
</script>

<script>
/* Dashboard mobile sidebar toggle (shared across all dashboard pages) */
(function(){
  var sidebar = document.getElementById('sidebar');
  var scrim   = document.getElementById('sidebarScrim');
  var openBtn = document.getElementById('sidebarToggle');
  var closeBtn= document.getElementById('sidebarClose');
  if (!sidebar || !scrim || !openBtn) return;

  function canScroll(el, deltaY){
    // Walk up from the event target to see if any ancestor inside the sidebar
    // is a scrollable container that still has room to scroll in the wheel direction.
    var node = el;
    while (node && sidebar.contains(node)){
      var isScrollable = node.scrollHeight - node.clientHeight > 2;
      if (isScrollable){
        var atTop = node.scrollTop <= 0;
        var atBottom = node.scrollTop + node.clientHeight >= node.scrollHeight - 1;
        if (deltaY > 0 && !atBottom) return true; // scrolling down & room
        if (deltaY < 0 && !atTop) return true;    // scrolling up & room
      }
      node = node.parentElement;
    }
    return false;
  }
  function onScrollAttempt(e){
    // Allow scroll only inside the sidebar IF the sidebar (or a child) can
    // actually scroll further in that direction. Otherwise block it.
    var deltaY = e.deltaY || 0;
    if (sidebar.contains(e.target) && canScroll(e.target, deltaY)) return;
    e.preventDefault();
  }
  function isTouchScrollable(el, startY){
    // For touch: if we started inside a scrollable child of the sidebar that
    // isn't at either edge, allow the touchmove.
    var node = el;
    while (node && sidebar.contains(node)){
      if (node.scrollHeight - node.clientHeight > 2) return true;
      node = node.parentElement;
    }
    return false;
  }
  var touchStartY = 0;
  function onTouchStart(e){
    touchStartY = e.touches ? e.touches[0].clientY : 0;
  }
  function onTouchMove(e){
    if (sidebar.contains(e.target) && isTouchScrollable(e.target, touchStartY)) return;
    e.preventDefault();
  }
  function onKeyScrollAttempt(e){
    var keys = [32,33,34,35,36,37,38,39,40];
    if (keys.indexOf(e.keyCode) === -1) return;
    if (sidebar.contains(e.target)) return; // allow typing/scrolling in form inputs inside sidebar (none today but safe)
    e.preventDefault();
  }

  function set(open){
    sidebar.classList.toggle('is-open', open);
    scrim.classList.toggle('is-open', open);
    if (open){
      window.addEventListener('wheel', onScrollAttempt, {capture:true, passive:false});
      window.addEventListener('touchstart', onTouchStart, {capture:true, passive:true});
      window.addEventListener('touchmove', onTouchMove, {capture:true, passive:false});
      window.addEventListener('keydown', onKeyScrollAttempt, {capture:true});
    } else {
      window.removeEventListener('wheel', onScrollAttempt, {capture:true, passive:false});
      window.removeEventListener('touchstart', onTouchStart, {capture:true, passive:true});
      window.removeEventListener('touchmove', onTouchMove, {capture:true, passive:false});
      window.removeEventListener('keydown', onKeyScrollAttempt, {capture:true});
    }
  }
  openBtn.addEventListener('click', function(e){ e.stopPropagation(); set(true); });
  if (closeBtn) closeBtn.addEventListener('click', function(){ set(false); });
  scrim.addEventListener('click', function(){ set(false); });
  // Close when a nav link is tapped
  sidebar.querySelectorAll('nav a').forEach(function(a){
    a.addEventListener('click', function(){ set(false); });
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') set(false);
  });
})();
</script>
</body>
</html>
