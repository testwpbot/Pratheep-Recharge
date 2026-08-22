<!DOCTYPE html>
<html lang="en" class="is-loading">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Admin Panel') — {{ config('app.name') }}</title>
<link rel="icon" type="image/png" href="{{ asset('assets/logo-mark.png') }}">
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
<link rel="stylesheet" href="{{ asset('css/loader.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@stack('styles')
</head>
<body data-shell="app">

@include('partials.loader')

<div class="app-shell">
  <aside class="app-sidebar" id="sidebar">
    <div class="app-sidebar__brand">
      <img src="{{ asset('assets/logo-mark.png') }}" alt="">
      <div>
        <h1>Happy Pratheep</h1>
        <small>Admin Panel</small>
      </div>
    </div>

    <nav>
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <x-icon name="home" :size="18" /> Dashboard
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
<script>
(function(){
  function closeAll(except){
    document.querySelectorAll('[data-hpr-dd].is-open').forEach(function(dd){
      if (dd === except) return;
      dd.classList.remove('is-open');
      var m = dd.querySelector('.hpr-dd__menu');
      if (m) m.hidden = true;
    });
  }
  function place(dd){
    var menu = dd.querySelector('.hpr-dd__menu');
    var btn = dd.querySelector('.hpr-dd__btn');
    if (!menu || !btn) return;
    var r = btn.getBoundingClientRect();
    var w = Math.max(r.width, 140);
    menu.style.position = 'fixed';
    menu.style.minWidth = w + 'px';
    menu.style.left = r.left + 'px';
    menu.style.top = (r.bottom + 6) + 'px';
    menu.style.right = 'auto';
    var space = window.innerHeight - r.bottom - 16;
    if (space < 160){
      menu.style.top = 'auto';
      menu.style.bottom = (window.innerHeight - r.top + 6) + 'px';
      menu.style.maxHeight = Math.max(140, r.top - 16) + 'px';
    } else {
      menu.style.bottom = 'auto';
      menu.style.maxHeight = Math.min(280, space) + 'px';
    }
  }
  document.addEventListener('click', function(e){
    var btn = e.target.closest('[data-hpr-dd] .hpr-dd__btn');
    var item = e.target.closest('[data-hpr-dd] .hpr-dd__item');
    if (btn){
      e.preventDefault(); e.stopPropagation();
      var dd = btn.closest('[data-hpr-dd]');
      var open = dd.classList.contains('is-open');
      closeAll();
      if (!open){
        dd.classList.add('is-open');
        var menu = dd.querySelector('.hpr-dd__menu');
        if (menu){ menu.hidden = false; place(dd); }
      }
      return;
    }
    if (item){
      e.preventDefault(); e.stopPropagation();
      var dd = item.closest('[data-hpr-dd]');
      var val = item.getAttribute('data-value');
      var label = item.getAttribute('data-label') || item.textContent.trim();
      var hidden = dd.querySelector('input[type=hidden]');
      if (hidden) hidden.value = val;
      var lab = dd.querySelector('.hpr-dd__label');
      if (lab) lab.textContent = label;
      dd.querySelectorAll('.hpr-dd__item').forEach(function(i){ i.classList.toggle('is-active', i === item); });
      closeAll();
      var formId = dd.getAttribute('data-auto-submit');
      if (formId){
        var form = document.getElementById(formId);
        if (form) form.submit();
      }
      return;
    }
    closeAll();
  });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeAll(); });
  window.addEventListener('resize', function(){ closeAll(); });
  window.addEventListener('scroll', function(){ closeAll(); }, true);
})();
</script>
@stack('scripts')
</body>
</html>
