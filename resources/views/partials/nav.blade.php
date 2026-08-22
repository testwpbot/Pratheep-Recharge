<!-- ============ NAV ============ -->
<header class="nav" id="nav">
  <div class="wrap">
    <a href="{{ route('home') }}" class="brand" aria-label="Happy Pratheep Recharge home">
      <img class="brand__mark" src="{{ asset('assets/logo-mark.png') }}" alt="">
      <span class="brand__text">
        <span class="brand__l1"><span class="happy">Happy</span><span class="pratheep">PRATHEEP</span></span>
        <span class="brand__l2"><i></i><span>Recharge</span><i></i></span>
      </span>
    </a>

    <ul class="nav__links">
      <li><a href="{{ route('home') }}" class="lnk {{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>

      <li class="has-menu">
        <a href="{{ route('recharge.category', 'mobile') }}" class="lnk">Mobile Reload
          <x-icon name="caret" class="caret" :size="11"/>
        </a>
        <ul class="menu">
          <li><a href="{{ route('recharge.category', 'mobile') }}">
            <span class="ic"><x-icon name="phone-menu" :size="18"/></span>
            <span>Prepaid Top-Up<small>Dialog · Mobitel · Hutch · Airtel</small></span>
          </a></li>
          <li><a href="{{ route('recharge.category', 'mobile') }}">
            <span class="ic"><x-icon name="bill" :size="18"/></span>
            <span>Postpaid Bill<small>Pay your monthly mobile bill</small></span>
          </a></li>
          <li><a href="{{ route('recharge.category', 'mobile') }}">
            <span class="ic"><x-icon name="wifi" :size="18"/></span>
            <span>Data Packages<small>Daily, weekly &amp; monthly bundles</small></span>
          </a></li>
        </ul>
      </li>

      <li class="has-menu">
        <a href="{{ route('recharge.category', 'broadband') }}" class="lnk">ISP Bills
          <x-icon name="caret" class="caret" :size="11"/>
        </a>
        <ul class="menu">
          <li><a href="{{ route('recharge.category', 'broadband') }}">
            <span class="ic"><x-icon name="tv" :size="18"/></span>
            <span>Home Broadband<small>SLT-Mobitel Fibre &amp; ADSL</small></span>
          </a></li>
          <li><a href="{{ route('recharge.category', 'broadband') }}">
            <span class="ic"><x-icon name="wifi" :size="18"/></span>
            <span>4G / 5G Router<small>Dialog · Lanka Bell · Hutch</small></span>
          </a></li>
          <li><a href="{{ route('recharge.category', 'broadband') }}">
            <span class="ic"><x-icon name="upload" :size="18"/></span>
            <span>Data Add-On<small>Extend or boost your quota</small></span>
          </a></li>
        </ul>
      </li>

      <li class="has-menu">
        <a href="{{ route('recharge.category', 'utility') }}" class="lnk">Utility Bills
          <x-icon name="caret" class="caret" :size="11"/>
        </a>
        <ul class="menu">
          <li><a href="{{ route('recharge.category', 'utility') }}">
            <span class="ic"><x-icon name="bolt" :size="18"/></span>
            <span>Electricity — CEB / LECO<small>Settle your CEB or LECO unit bill</small></span>
          </a></li>
          <li><a href="{{ route('recharge.category', 'utility') }}">
            <span class="ic"><x-icon name="drop" :size="18"/></span>
            <span>Water — NWSDB<small>National Water Supply Board</small></span>
          </a></li>
          <li><a href="{{ route('recharge.category', 'tv') }}">
            <span class="ic"><x-icon name="plus" :size="18"/></span>
            <span>TV &amp; Insurance<small>Dialog TV, PEO TV, premiums</small></span>
          </a></li>
        </ul>
      </li>

      <li><a href="{{ route('gift-cards') }}" class="lnk">Gift Cards</a></li>
      <li><a href="{{ route('support') }}" class="lnk">Support</a></li>
    </ul>

    <div class="nav__cta">
      @auth
        {{-- Logged-in state: avatar + email account chip with dropdown --}}
        <div class="account-menu" id="accountMenu">
          <button type="button" class="user-chip" id="accountBtn" aria-haspopup="true" aria-expanded="false">
            <img class="user-chip__avatar" src="{{ auth()->user()->avatarUrl(48) }}" alt="{{ auth()->user()->name }}">
            <span class="user-chip__email">{{ auth()->user()->email }}</span>
            <svg class="user-chip__caret" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </button>
          <div class="account-dropdown" id="accountDropdown" role="menu">
            <div class="account-dropdown__head">
              <img src="{{ auth()->user()->avatarUrl(64) }}" alt="">
              <div>
                <b>{{ auth()->user()->name }}</b>
                <small>{{ auth()->user()->email }}</small>
              </div>
            </div>
            <a href="{{ route('dashboard') }}" class="account-dropdown__item" role="menuitem">
              <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V21h13V9.5"/><path d="M10 21v-6h4v6"/>
              </svg>
              Dashboard
            </a>
            <a href="{{ route('recharge.history') }}" class="account-dropdown__item" role="menuitem">
              <x-icon name="bill" :size="17"/>
              My Orders
            </a>
            <a href="{{ route('dashboard.plans') }}" class="account-dropdown__item" role="menuitem">
              <x-icon name="tag" :size="17"/>
              Plans & Rates
            </a>
            <a href="{{ route('recharge.index') }}" class="account-dropdown__item" role="menuitem">
              <x-icon name="bolt-nav" :size="17"/>
              Recharge Now
            </a>
            @if(auth()->user()->is_admin)
              <a href="{{ route('admin.dashboard') }}" class="account-dropdown__item" role="menuitem">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2 3 7v5c0 5 4 9 9 10 5-1 9-5 9-10V7l-9-5z"/>
                </svg>
                Admin Panel
              </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="account-dropdown__form">
              @csrf
              <button type="submit" class="account-dropdown__logout" role="menuitem" data-loading="Signing out…" style="position:relative;">
                <span class="btn-label">
                  <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                  </svg>
                  Logout
                </span>
                <span class="btn-spinner" hidden></span>
              </button>
            </form>
          </div>
        </div>
      @else
        {{-- Guest state: sign in + sign up --}}
        <a href="{{ route('login') }}" class="btn-fill">
          <x-icon name="user" :size="17"/>
          Sign In
        </a>
        <a href="{{ route('register') }}" class="btn btn--gold">
          <x-icon name="bolt-nav" :size="17"/>
          Sign Up
        </a>
      @endauth
      <button class="burger" id="burger" aria-label="Open menu"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>

<style>
  /* ---- logged-in user chip in nav ---- */
  .user-chip{
    display:inline-flex;align-items:center;gap:10px;
    padding:4px 14px 4px 4px;height:44px;border-radius:999px;
    background:rgba(255,255,255,.85);
    border:1.5px solid rgba(11,42,91,.12);
    box-shadow:var(--shadow-sm);
    transition:border-color .25s,box-shadow .25s,transform .25s;
    max-width:250px;
  }
  .nav.is-stuck .user-chip{background:#fff;}
  .user-chip:hover{border-color:rgba(232,163,23,.55);box-shadow:0 6px 18px rgba(7,27,61,.12);}
  .user-chip__avatar{
    width:36px;height:36px;border-radius:50%;
    object-fit:cover;flex:none;
    border:2px solid #fff;
    box-shadow:0 2px 6px rgba(7,27,61,.15);
  }
  .user-chip__email{
    font-size:13px;font-weight:700;color:var(--navy-800);
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;
  }
  @media (max-width:1000px){
    .user-chip{display:none;}
  }
  .drawer__who{
    display:flex; align-items:center; gap:10px;
    margin:0 0 12px; min-width:0;
  }
  .drawer__who img{
    width:36px; height:36px; border-radius:50%; object-fit:cover; flex:none;
    border:2px solid var(--gold-500);
  }
  .drawer__who span{
    min-width:0; font-size:13px; font-weight:700; color:var(--navy-800);
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
  }
</style>

<!-- mobile drawer -->
<div class="scrim" id="scrim"></div>
<aside class="drawer" id="drawer">
  <div class="drawer__head">
    <a href="{{ route('home') }}" class="brand">
      <img class="brand__mark" src="{{ asset('assets/logo-mark.png') }}" alt="">
      <span class="brand__text">
        <span class="brand__l1"><span class="happy">Happy</span><span class="pratheep">PRATHEEP</span></span>
        <span class="brand__l2"><i></i><span>Recharge</span><i></i></span>
      </span>
    </a>
    <button class="x" id="close">&times;</button>
  </div>
  <nav class="drawer__nav">
    <p class="drawer__label">Menu</p>
    <a href="{{ route('home') }}"><span class="di"><x-icon name="home" :size="18"/></span>Home</a>
    <a href="{{ route('recharge.category', 'mobile') }}"><span class="di"><x-icon name="mobile-dr" :size="18"/></span>Mobile Reload</a>
    <a href="{{ route('recharge.category', 'mobile') }}"><span class="di"><x-icon name="postpaid-dr" :size="18"/></span>Postpaid Bill</a>
    <a href="{{ route('recharge.category', 'mobile') }}"><span class="di"><x-icon name="wifi-dr" :size="18"/></span>Data Packages</a>
    <a href="{{ route('recharge.category', 'broadband') }}"><span class="di"><x-icon name="router-dr" :size="18"/></span>ISP / Broadband Bills</a>
    <a href="{{ route('recharge.category', 'utility') }}"><span class="di"><x-icon name="bolt-dr" :size="18"/></span>Electricity — CEB / LECO</a>
    <a href="{{ route('recharge.category', 'utility') }}"><span class="di"><x-icon name="drop-dr" :size="18"/></span>Water — NWSDB</a>
    <a href="{{ route('gift-cards') }}"><span class="di"><x-icon name="gift-dr" :size="18"/></span>Gift Cards</a>
    <a href="{{ route('support') }}"><span class="di"><x-icon name="headset-dr" :size="18"/></span>Support</a>
  </nav>

  <div class="drawer__account">
    <p class="drawer__label">Account</p>
    @auth
      <div class="drawer__who">
        <img src="{{ auth()->user()->avatarUrl(40) }}" alt="">
        <span>{{ auth()->user()->email }}</span>
      </div>
      <a href="{{ route('dashboard') }}" class="btn btn--navy" style="width:100%;justify-content:center;">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V21h13V9.5"/><path d="M10 21v-6h4v6"/>
        </svg>
        Dashboard
      </a>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn" style="width:100%;margin-top:9px;background:#d43b3b;color:#fff;box-shadow:0 8px 20px rgba(212,59,59,.3);justify-content:center;position:relative;" data-loading="Signing out…">
          <span class="btn-label">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Logout
          </span>
          <span class="btn-spinner" hidden></span>
        </button>
      </form>
    @else
      <a href="{{ route('recharge.index') }}" class="btn btn--gold">
        <x-icon name="bolt-nav" :size="17"/>
        Recharge Now
      </a>
      <a href="{{ route('login') }}" class="btn btn--ghost" style="margin-top:9px;">
        <x-icon name="user" :size="17"/>
        Sign In
      </a>
      <a href="{{ route('register') }}" class="btn btn--navy" style="margin-top:9px;width:100%;">
        Create Account
      </a>
    @endauth
  </div>
</aside>
