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
@stack('scripts')
</body>
</html>
