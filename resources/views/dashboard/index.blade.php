@extends('layouts.dashboard')
@section('title', 'My Dashboard')
@section('dash_compact', '1')

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
      @if(!empty($walletNotice))
        <span style="color:var(--gold-600); font-weight:700;">{{ in_array($walletNotice['type'] ?? '', ['low', 'reserve'], true) ? 'Keep LKR '.number_format($walletNotice['min'] ?? 100, 0).' — add money →' : 'Add money to start →' }}</span>
      @else
        <span style="color:var(--gold-600); font-weight:700;">Top up via bank transfer →</span>
      @endif
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

    <div class="kind-tabs" id="mobileKindTabs" role="tablist" aria-label="Prepaid or Postpaid"
         @if(($categories->first()->slug ?? '') !== 'mobile') hidden @endif>
      <button type="button" class="kind-tab active" data-kind="" aria-selected="true">All</button>
      <button type="button" class="kind-tab" data-kind="prepaid" aria-selected="false">Prepaid</button>
      <button type="button" class="kind-tab" data-kind="postpaid" aria-selected="false">Postpaid</button>
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
                @php
                  $svcType = strtolower((string) $s->type);
                  $catSlug = strtolower((string) $cat->slug);
                  $isBill = in_array($svcType, ['utility','postpaid','bill','insurance','wallet'], true)
                    || in_array($catSlug, ['utility','insurance','wallet-topup'], true);
                  $payKind = $svcType === 'postpaid' ? 'postpaid' : 'prepaid';
                @endphp
                <button type="button"
                        class="service-card"
                        data-rc-custom
                        data-service-id="{{ $s->id }}"
                        data-logo="{{ $s->logoUrl }}"
                        data-op-name="{{ $s->name }}"
                        data-mode="{{ $isBill ? 'bill' : 'reload' }}"
                        data-category="{{ $catSlug }}"
                        data-pay-kind="{{ $payKind }}"
                        data-hide-notify="{{ ($catSlug === 'mobile' || $svcType === 'postpaid') ? '1' : '0' }}">
                  <img src="{{ $s->logoUrl }}" alt="{{ $s->name }}"
                       onerror="this.src='{{ asset('assets/logo-mark.png') }}'">
                  <h4>{{ $s->name }}</h4>
                  <small>{{ ucfirst($s->type) }}</small>
                </button>
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
            <td>{{ $o->customerServiceName() }}</td>
            <td>{{ $o->account_number }}</td>
            <td><b>LKR {{ number_format($o->amount, 2) }}</b></td>
            <td>LKR {{ number_format($o->profit, 2) }}</td>
            <td><span class="pill pill--{{ $o->status }}">{{ $o->statusLabel() }}</span></td>
            <td><small>{{ $o->created_at->format('Y-m-d H:i') }}</small></td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align:center; padding:24px; color:var(--muted);">No orders yet. Pick a service above to start your first recharge 🚀</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@include('partials.recharge-modal')

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

.kind-tabs{
  display:flex; gap:8px; flex-wrap:wrap;
  padding:6px; background:#fff; border:1px solid var(--line);
  border-radius:12px; margin:-8px 0 22px;
}
.kind-tabs[hidden]{display:none !important;}
.kind-tab{
  flex:1 1 0; min-width:0;
  padding:9px 12px; border-radius:9px; border:0; background:transparent;
  font:inherit; font-weight:700; font-size:13.5px; color:var(--navy-800);
  cursor:pointer; transition:.18s;
}
.kind-tab:hover{background:rgba(11,42,91,.06);}
.kind-tab.active{
  background:linear-gradient(135deg,var(--gold-300),var(--gold-500));
  color:#2a1a00;
  box-shadow:0 4px 10px rgba(232,163,23,.28);
}
.service-card.is-kind-hidden{display:none !important;}

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
  .kind-tabs{gap:6px; padding:6px; margin-top:-4px;}
  .kind-tab{padding:10px 8px; font-size:13px;}
}
@media (max-width:540px){
  .dash-hero{margin-bottom:12px; gap:10px;}
  .dash-hero__greet{padding:16px 16px; border-radius:16px;}
  .dash-hero__greet h2{font-size:20px; margin-bottom:4px;}
  .dash-hero__greet p{font-size:13.5px;}
  .wallet-cards{flex-direction:row; gap:8px;}
  .wcard{padding:12px 12px; border-radius:14px; min-width:0;}
  .wcard b{font-size:16px;}
  .wcard small{font-size:10px;}
  .wcard span{font-size:11px;}
  .stats-grid{gap:8px; margin-bottom:12px;}
  .stat{padding:12px 10px;}
  .stat b{font-size:18px;}
  .service-grid{grid-template-columns:repeat(2,1fr); gap:10px;}
  .service-card{
    padding:16px 10px;
    border-radius:14px;
    min-height:140px;
    gap:8px;
  }
  .service-card img{width:52px; height:52px;}
  .service-card h4{font-size:13.5px; margin:0;}
  .service-card small{font-size:11px;}
  .service-card .cb-badge{font-size:9px; padding:3px 7px; top:8px; right:8px;}
}
@media (max-width:380px){
  .service-grid{gap:8px;}
  .service-card{padding:14px 8px; min-height:128px;}
  .service-card img{width:46px; height:46px;}
}
</style>
@endpush

@push('scripts')
<script>
(function(){
  var tabs = document.querySelectorAll('#rechargeTabs .cat-tab');
  var panels = document.querySelectorAll('.service-panel');
  var tabsEl = document.getElementById('rechargeTabs');
  var kindTabs = document.getElementById('mobileKindTabs');
  if (!tabs.length) return;

  function currentKind(){
    if (!kindTabs) return '';
    var on = kindTabs.querySelector('.kind-tab.active');
    return on ? (on.dataset.kind || '') : '';
  }
  function applyMobileKind(){
    var panel = document.getElementById('panel-mobile');
    if (!panel) return;
    var kind = currentKind();
    panel.querySelectorAll('.service-card').forEach(function(card){
      var match = !kind || card.dataset.payKind === kind;
      card.classList.toggle('is-kind-hidden', !match);
    });
  }
  function syncKindTabs(slug){
    if (!kindTabs) return;
    kindTabs.hidden = slug !== 'mobile';
    applyMobileKind();
  }

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
    syncKindTabs(slug);
    if (updateHash){
      var newUrl = location.pathname + '#' + slug;
      history.replaceState(null, '', newUrl);
    }
  }

  if (kindTabs){
    kindTabs.querySelectorAll('.kind-tab').forEach(function(btn){
      btn.addEventListener('click', function(){
        kindTabs.querySelectorAll('.kind-tab').forEach(function(t){
          var on = t === btn;
          t.classList.toggle('active', on);
          t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        applyMobileKind();
      });
    });
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
