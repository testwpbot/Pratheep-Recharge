@extends('layouts.dashboard')
@section('title', 'My Dashboard')

@section('content')

{{-- WELCOME + WALLET ROW --}}
<div class="dash-hero">
  <div class="dash-hero__greet">
    <h2>Hi, {{ explode(' ', $user->name)[0] }} 👋</h2>
    <p>What would you like to pay or top-up today?</p>
  </div>
  <div class="wallet-cards">
    <a href="{{ route('earnings') }}" class="wcard wcard--gold" style="text-decoration:none; color:inherit;">
      <small>Total Cashback Earned</small>
      <b>LKR {{ number_format($stats['total_cashback'], 2) }}</b>
      <span>From successful recharges · View history →</span>
    </a>
    <a href="{{ route('wallet') }}" class="wcard" style="text-decoration:none; color:inherit;">
      <small>Wallet Balance</small>
      <b>LKR {{ number_format($stats['balance'], 2) }}</b>
      <span style="color:var(--gold-600); font-weight:700;">Top up via bank transfer →</span>
    </a>
  </div>
</div>

{{-- STATS --}}
<div class="stats-grid">
  <div class="stat"><b>{{ $stats['total_orders'] }}</b><span>Total Orders</span></div>
  <div class="stat"><b>{{ $stats['successful'] }}</b><span>Successful</span></div>
  <div class="stat"><b>LKR {{ number_format($stats['total_spent'], 2) }}</b><span>Total Spent</span></div>
  <div class="stat"><b>{{ $stats['total_orders'] ? number_format(($stats['successful'] / $stats['total_orders']) * 100, 0) : 0 }}%</b><span>Success Rate</span></div>
</div>

{{-- QUICK RECHARGE — all categories loaded, JS-driven tab switch --}}
<div class="card">
  <div class="card__head">
    <h3>Quick Recharge</h3>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a href="{{ route('dashboard.plans') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
        <x-icon name="tag" :size="14"/> View all plans
      </a>
      <a href="{{ route('recharge.history') }}" class="btn-admin btn-admin--ghost btn-admin--sm">
        <x-icon name="bill" :size="14"/> My Orders
      </a>
    </div>
  </div>

  @if ($categories->isEmpty())
    <div style="text-align:center;padding:40px;color:var(--muted);">
      No services available yet. Please check back soon.
    </div>
  @else
    <div class="cat-tabs" role="tablist" id="rechargeTabs">
      @foreach ($categories as $idx => $cat)
        <button type="button"
                role="tab"
                class="cat-tab {{ $loop->first ? 'active' : '' }}"
                data-cat-slug="{{ $cat->slug }}"
                id="tab-{{ $cat->slug }}"
                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                aria-controls="panel-{{ $cat->slug }}">
          {{ $cat->name }}
        </button>
      @endforeach
    </div>

    <div class="service-panels" id="rechargePanels">
      @foreach ($categories as $cat)
        <div class="service-panel {{ $loop->first ? 'is-active' : '' }}"
             id="panel-{{ $cat->slug }}"
             role="tabpanel"
             aria-labelledby="tab-{{ $cat->slug }}">

          @if ($servicesByCategory->get($cat->slug)?->isNotEmpty())
            <div class="service-grid">
              @foreach ($servicesByCategory->get($cat->slug) as $s)
                <a href="{{ route('recharge.form', $s) }}" class="service-card">
                  @if ((float) $s->profit > 0)
                    <span class="cb-badge">
                      @if ($s->profit_type === 'PCT') {{ number_format($s->profit, 2) }}% cashback
                      @else LKR {{ number_format($s->profit, 2) }} cashback @endif
                    </span>
                  @endif
                  <img src="{{ $s->logoUrl }}" alt="{{ $s->name }}"
                       onerror="this.src='{{ asset('assets/logo-mark.png') }}'">
                  <h4>{{ $s->name }}</h4>
                  <small>{{ ucfirst($s->type) }}</small>
                </a>
              @endforeach
            </div>
          @else
            <div style="text-align:center;padding:40px;color:var(--muted);">
              No services available in {{ $cat->name }} yet.
            </div>
          @endif
        </div>
      @endforeach
    </div>
  @endif
</div>

{{-- RECENT ORDERS --}}
<div class="card" style="margin-top:20px;">
  <div class="card__head">
    <h3>Recent Orders</h3>
    <a href="{{ route('recharge.history') }}" class="btn-admin btn-admin--ghost btn-admin--sm">View all</a>
  </div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Reference</th><th>Service</th><th>Account</th><th>Amount</th><th>Cashback</th><th>Status</th><th>Date</th></tr>
      </thead>
      <tbody>
        @forelse ($orders as $o)
          <tr>
            <td><a href="{{ route('recharge.show', $o) }}" style="color:var(--gold-500); font-weight:700;">{{ $o->reference }}</a></td>
            <td>{{ $o->service->name }}</td>
            <td>{{ $o->account_number }}</td>
            <td><b>LKR {{ number_format($o->amount, 2) }}</b></td>
            <td>LKR {{ number_format($o->profit, 2) }}</td>
            <td><span class="pill pill--{{ $o->status }}">{{ ucfirst($o->status) }}</span></td>
            <td><small>{{ $o->created_at->format('Y-m-d H:i') }}</small></td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align:center; padding:24px; color:var(--muted);">No orders yet. Pick a service above to start your first recharge 🚀</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection

@push('styles')
<style>
.dash-hero{
  display:grid; gap:18px; grid-template-columns:1fr auto;
  margin-bottom:22px; align-items:stretch;
}
.dash-hero__greet{
  background:linear-gradient(135deg,#0b2a5b,#071b3d);
  color:#fff; border-radius:20px; padding:26px 28px; position:relative; overflow:hidden;
}
.dash-hero__greet::before{
  content:"";position:absolute;right:-40px;bottom:-40px;width:200px;height:200px;
  background:radial-gradient(circle,rgba(232,163,23,.35),transparent 70%);
  border-radius:50%;
}
.dash-hero__greet h2{margin:0 0 6px;font-size:26px;font-weight:800;letter-spacing:-.02em;}
.dash-hero__greet p{margin:0;opacity:.85;font-weight:500;}
.wallet-cards{display:flex; gap:14px;}
.wcard{
  background:#fff;border:1px solid var(--line);border-radius:20px;padding:20px 22px;min-width:190px;
  box-shadow:var(--shadow-sm);display:flex;flex-direction:column;gap:4px;
}
.wcard small{font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);font-weight:700;}
.wcard b{font-size:24px;font-weight:800;color:var(--navy-900);letter-spacing:-.01em;}
.wcard span{font-size:12px;color:var(--muted);font-weight:500;}
.wcard--gold{background:linear-gradient(135deg,var(--gold-300),var(--gold-500));border-color:transparent;}
.wcard--gold b{color:#2a1a00;}
.wcard--gold small{color:#5a3e00;}
.wcard--gold span{color:#4a3200;}

/* ---------- Category tabs (clean wrap, no sliding indicator) ---------- */
.cat-tabs{
  position:relative; display:flex; gap:8px; flex-wrap:wrap;
  padding:8px; background:#f7f9fd; border:1px solid var(--line); border-radius:16px;
  margin-bottom:22px;
}
.cat-tab{
  position:relative;
  flex:1 1 auto;
  min-width:0;
  padding:10px 14px; border-radius:10px; border:0; background:transparent; cursor:pointer;
  font:inherit; font-weight:700; color:var(--navy-800); font-size:14px;
  white-space:nowrap;
  transition:background .2s ease, color .2s ease, transform .12s ease;
}
.cat-tab:hover{background:rgba(11,42,91,.06); color:var(--navy-700);}
.cat-tab:active{transform:scale(.97);}
.cat-tab.active{
  background:linear-gradient(135deg,var(--navy-700),var(--navy-900));
  color:#fff;
  box-shadow:0 6px 14px rgba(7,27,61,.25);
}

/* ---------- Service panels (fade + slide animation) ---------- */
.service-panels{position:relative; min-height:200px;}
.service-panel{
  opacity:0; transform:translateY(8px);
  position:absolute; inset:0; pointer-events:none; visibility:hidden;
  transition:opacity .28s ease, transform .28s ease;
}
.service-panel.is-active{
  opacity:1; transform:translateY(0);
  position:relative; pointer-events:auto; visibility:visible;
}
.service-panel.is-leaving{
  opacity:0; transform:translateY(-6px);
}

@media (max-width:820px){
  .dash-hero{grid-template-columns:1fr;}
  .wallet-cards{flex-direction:row;}
  .wcard{flex:1;min-width:0;}
  .service-grid{grid-template-columns:repeat(3,1fr); gap:14px;}
}
@media (max-width:580px){
  .cat-tabs{gap:6px; padding:6px;}
  .cat-tab{flex:1 1 calc(50% - 6px); padding:11px 8px; font-size:13px;}
}
@media (max-width:540px){
  .wallet-cards{flex-direction:column;}
  .service-grid{grid-template-columns:repeat(2,1fr); gap:14px;}
  .service-card{
    padding:22px 14px;
    border-radius:16px;
    min-height:170px;
    gap:10px;
  }
  .service-card img{width:64px; height:64px;}
  .service-card h4{font-size:14px; margin:0;}
  .service-card small{font-size:11px;}
  .service-card .cb-badge{font-size:9px; padding:3px 7px; top:8px; right:8px;}
}
@media (max-width:380px){
  .service-grid{gap:10px;}
  .service-card{padding:18px 10px; min-height:150px;}
  .service-card img{width:56px; height:56px;}
}
</style>
@endpush

@push('scripts')
<script>
(function(){
  var tabs = document.querySelectorAll('#rechargeTabs .cat-tab');
  var panels = document.querySelectorAll('.service-panel');
  var tabsEl = document.getElementById('rechargeTabs');
  if (!tabs.length) return;

  function activate(tab, updateHash){
    var slug = tab.dataset.catSlug;
    tabs.forEach(function(t){
      var on = t === tab;
      t.classList.toggle('active', on);
      t.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    panels.forEach(function(p){
      if (p.id === 'panel-' + slug){
        p.classList.remove('is-leaving');
        p.classList.add('is-active');
      } else if (p.classList.contains('is-active')){
        p.classList.add('is-leaving');
        p.classList.remove('is-active');
        setTimeout(function(){ p.classList.remove('is-leaving'); }, 280);
      }
    });
    if (updateHash){
      var newUrl = location.pathname + '#' + slug;
      history.replaceState(null, '', newUrl);
    }
  }

  tabs.forEach(function(tab){
    tab.addEventListener('click', function(){ activate(tab, true); });
  });

  // Keyboard nav
  tabsEl.addEventListener('keydown', function(e){
    var current = tabsEl.querySelector('.cat-tab.active');
    if (!current) return;
    var arr = Array.from(tabs);
    var i = arr.indexOf(current);
    var next = null;
    if (e.key === 'ArrowRight') next = arr[(i+1) % arr.length];
    if (e.key === 'ArrowLeft')  next = arr[(i-1+arr.length) % arr.length];
    if (next){ next.focus(); activate(next, true); e.preventDefault(); }
  });

  // Deep-link via hash
  if (location.hash){
    var slug = location.hash.replace('#','');
    var match = tabsEl.querySelector('.cat-tab[data-cat-slug="'+slug+'"]');
    if (match){ activate(match, false); }
  }
})();
</script>
@endpush
