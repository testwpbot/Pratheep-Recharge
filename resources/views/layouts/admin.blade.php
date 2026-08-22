<!DOCTYPE html>
<html lang="en" class="is-loading">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Admin Panel') — {{ config('app.name') }}</title>
@include('partials.favicon')
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
<link rel="stylesheet" href="{{ asset('css/loader.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@stack('styles')
</head>
<body data-shell="app">

@include('partials.loader')

<div class="app-shell">
  <div class="sidebar-scrim" id="sidebarScrim"></div>

  <aside class="app-sidebar" id="sidebar">
    <div class="app-sidebar__brand">
      <img src="{{ asset('assets/logo-mark.png') }}" alt="">
      <div>
        <h1>Happy Pratheep</h1>
        <small>Admin Panel</small>
      </div>
      <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu">&times;</button>
    </div>

    <nav>
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <x-icon name="home" :size="18" /> Dashboard
      </a>
      <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <x-icon name="users" :size="18" /> Users
      </a>
      <a href="{{ route('admin.funds.index') }}" class="{{ request()->routeIs('admin.funds.*') ? 'active' : '' }}">
        <x-icon name="wallet" :size="18" /> Provider Money
        @php $fundLow = \Illuminate\Support\Facades\Cache::get('funds:overall_status') === 'low'; @endphp
        @if($fundLow) <em class="sb-badge">!</em> @endif
      </a>
      <a href="{{ route('admin.providers.index') }}" class="{{ request()->routeIs('admin.providers.*') ? 'active' : '' }}">
        <x-icon name="wifi" :size="18" /> Providers
      </a>
      <a href="{{ route('admin.services.index') }}" class="{{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
        <x-icon name="phone-menu" :size="18" /> Services &amp; Pricing
      </a>
      <a href="{{ route('admin.special-pricing.index') }}" class="{{ request()->routeIs('admin.special-pricing.*') ? 'active' : '' }}">
        <x-icon name="users" :size="18" /> Special Pricing
      </a>
      <a href="{{ route('admin.plans.index') }}" class="{{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
        <x-icon name="gift" :size="18" /> Plans / Packages
      </a>
      <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
        <x-icon name="bill" :size="18" /> Orders
      </a>
      <a href="{{ route('admin.deposits.index') }}" class="{{ request()->routeIs('admin.deposits.*') ? 'active' : '' }}">
        <x-icon name="wallet" :size="18" /> Deposits
        @php $pend = \App\Models\WalletDeposit::where('status','pending')->count(); @endphp
        @if($pend) <em class="sb-badge">{{ $pend }}</em> @endif
      </a>
      <a href="{{ route('admin.complaints.index') }}" class="{{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}">
        <x-icon name="alert" :size="18" /> Complaints
        @php $openCmp = \App\Models\Complaint::whereIn('status', ['open','in_progress'])->count(); @endphp
        @if($openCmp) <em class="sb-badge">{{ $openCmp }}</em> @endif
      </a>
      <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
        <x-icon name="settings" :size="18" /> Settings
      </a>
      <a href="{{ route('home') }}" target="_blank">
        <x-icon name="bolt-nav" :size="18" /> Visit Site
      </a>
    </nav>

    <div class="app-sidebar__footer">
      Logged in as<br><b style="color:#fff">{{ auth()->user()->name }}</b>
      <div style="margin-top:4px;color:var(--gold-400);font-weight:700;letter-spacing:.04em;">{{ auth()->user()->adminRoleLabel() }}</div>
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

<script>
/* Admin mobile sidebar — same drawer as the customer dashboard */
(function(){
  var sidebar = document.getElementById('sidebar');
  var scrim   = document.getElementById('sidebarScrim');
  var openBtn = document.getElementById('sidebarToggle');
  var closeBtn= document.getElementById('sidebarClose');
  if (!sidebar || !scrim || !openBtn) return;

  function canScroll(el, deltaY){
    var node = el;
    while (node && sidebar.contains(node)){
      var isScrollable = node.scrollHeight - node.clientHeight > 2;
      if (isScrollable){
        var atTop = node.scrollTop <= 0;
        var atBottom = node.scrollTop + node.clientHeight >= node.scrollHeight - 1;
        if (deltaY > 0 && !atBottom) return true;
        if (deltaY < 0 && !atTop) return true;
      }
      node = node.parentElement;
    }
    return false;
  }
  function onScrollAttempt(e){
    var deltaY = e.deltaY || 0;
    if (sidebar.contains(e.target) && canScroll(e.target, deltaY)) return;
    e.preventDefault();
  }
  function isTouchScrollable(el){
    var node = el;
    while (node && sidebar.contains(node)){
      if (node.scrollHeight - node.clientHeight > 2) return true;
      node = node.parentElement;
    }
    return false;
  }
  function onTouchMove(e){
    if (sidebar.contains(e.target) && isTouchScrollable(e.target)) return;
    e.preventDefault();
  }
  function onKeyScrollAttempt(e){
    var keys = [32,33,34,35,36,37,38,39,40];
    if (keys.indexOf(e.keyCode) === -1) return;
    if (sidebar.contains(e.target)) return;
    e.preventDefault();
  }

  function set(open){
    sidebar.classList.toggle('is-open', open);
    scrim.classList.toggle('is-open', open);
    openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open){
      window.addEventListener('wheel', onScrollAttempt, {capture:true, passive:false});
      window.addEventListener('touchmove', onTouchMove, {capture:true, passive:false});
      window.addEventListener('keydown', onKeyScrollAttempt, {capture:true});
    } else {
      window.removeEventListener('wheel', onScrollAttempt, {capture:true, passive:false});
      window.removeEventListener('touchmove', onTouchMove, {capture:true, passive:false});
      window.removeEventListener('keydown', onKeyScrollAttempt, {capture:true});
    }
  }
  openBtn.addEventListener('click', function(e){ e.stopPropagation(); set(true); });
  if (closeBtn) closeBtn.addEventListener('click', function(){ set(false); });
  scrim.addEventListener('click', function(){ set(false); });
  sidebar.querySelectorAll('nav a').forEach(function(a){
    a.addEventListener('click', function(){ set(false); });
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') set(false);
  });
  window.addEventListener('resize', function(){
    if (window.innerWidth > 900) set(false);
  });
})();
</script>
@stack('scripts')
</body>
</html>
