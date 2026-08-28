@extends('layouts.admin')
@section('title', 'Plans / Packages')

@push('styles')
<style>
/* ===== Plans toolbar ===== */
.plan-toolbar{
  display:flex; gap:10px; align-items:center; flex-wrap:wrap;
  margin-bottom:18px;
}
.plan-toolbar .search-wrap{
  position:relative; flex:1 1 260px; min-width:220px;
}
.plan-toolbar .search-wrap .si{
  position:absolute; left:14px; top:50%; transform:translateY(-50%);
  color:var(--muted); pointer-events:none;
}
.plan-toolbar .search-wrap input{
  width:100%; height:42px; padding:0 14px 0 42px;
  border:1.6px solid rgba(11,42,91,.16); border-radius:12px;
  background:#fff; font:inherit; font-size:14px; font-weight:600;
  color:var(--navy-800); transition:.2s;
}
.plan-toolbar .search-wrap input:focus{
  outline:none; border-color:var(--gold-500);
  box-shadow:0 0 0 4px rgba(232,163,23,.18);
}

/* ===== Custom dropdown ===== */
.adm-dd{position:relative;}
.adm-dd__btn{
  display:inline-flex; align-items:center; gap:8px;
  height:42px; padding:0 14px; border-radius:12px;
  border:1.6px solid rgba(11,42,91,.16); background:#fff;
  font:inherit; font-weight:700; font-size:13px; color:var(--navy-800);
  cursor:pointer; transition:.2s; white-space:nowrap;
}
.adm-dd__btn:hover{border-color:var(--gold-500); background:#fffdf6;}
.adm-dd.is-open .adm-dd__btn{
  border-color:var(--gold-500); box-shadow:0 0 0 4px rgba(232,163,23,.18);
}
.adm-dd__label{max-width:180px; overflow:hidden; text-overflow:ellipsis;}
.adm-dd__caret{color:var(--muted); transition:transform .2s;}
.adm-dd.is-open .adm-dd__caret{transform:rotate(180deg); color:var(--gold-500);}
.adm-dd__menu{
  position:absolute; top:calc(100% + 6px); left:0; right:auto; z-index:60;
  min-width:220px; max-height:320px; overflow-y:scroll; overflow-x:hidden;
  background:#fff; border:1px solid var(--line); border-radius:14px;
  box-shadow:0 16px 40px rgba(7,27,61,.22);
  padding:8px;
  scrollbar-width:none; -ms-overflow-style:none; overscroll-behavior:contain;
}
.adm-dd__menu::-webkit-scrollbar{width:0; height:0; display:none;}
.adm-dd__group{
  font-size:10.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase;
  color:var(--muted); padding:10px 10px 4px;
}
.adm-dd__item{
  display:flex; align-items:center; gap:9px;
  width:100%; padding:8px 10px; border:0; border-radius:9px;
  background:transparent; font:inherit; font-weight:600; font-size:13.5px;
  color:var(--navy-800); text-align:left; cursor:pointer; transition:.15s;
}
.adm-dd__item img{width:20px; height:20px; object-fit:contain; flex:none;}
.adm-dd__item svg{color:var(--gold-500); flex:none;}
.adm-dd__item:hover{background:rgba(11,42,91,.06);}
.adm-dd__item.is-active{
  background:linear-gradient(135deg,var(--navy-700),var(--navy-900)); color:#fff;
}
.adm-dd__item.is-active svg{color:var(--gold-400);}

/* ===== Plan table polish ===== */
.data-table td{vertical-align:middle;}
.data-table .op-cell{display:flex; align-items:center; gap:10px;}
.data-table .op-cell img{
  width:28px; height:28px; object-fit:contain; padding:4px;
  border:1px solid var(--line); border-radius:8px; background:#fff;
}
.data-table td .row-actions{
  display:flex; gap:6px; justify-content:flex-end; flex-wrap:wrap;
}
.data-table .empty{
  text-align:center; padding:36px 20px; color:var(--muted); font-weight:600;
}

/* ===== Pagination ===== */
.pg-wrap{
  margin-top:18px; display:flex; align-items:center; justify-content:space-between;
  gap:12px; flex-wrap:wrap;
}
.pg-info{font-size:13px; font-weight:700; color:var(--muted);}
.pg{
  display:inline-flex; align-items:center; gap:4px;
  background:#fff; border:1px solid var(--line); border-radius:12px;
  padding:6px; box-shadow:var(--shadow-sm);
}
.pg a, .pg span, .pg button{
  display:inline-flex; align-items:center; justify-content:center;
  min-width:36px; height:36px; padding:0 10px;
  border-radius:8px; font-weight:700; font-size:13px; color:var(--navy-800);
  text-decoration:none; border:0; background:transparent; cursor:pointer;
  transition:.18s;
}
.pg a:hover{background:rgba(11,42,91,.06);}
.pg .active{
  background:linear-gradient(135deg,var(--gold-300),var(--gold-500));
  color:#2a1a00; box-shadow:0 4px 10px rgba(232,163,23,.3);
}
.pg .disabled, .pg .disabled a, .pg span.disabled{
  opacity:.4; pointer-events:none; cursor:default;
}
.pg__dots{color:var(--muted);}

/* ===== Modal ===== */
.adm-modal{
  position:fixed; inset:0; z-index:500;
  display:flex; align-items:center; justify-content:center;
  padding:20px;
}
.adm-modal[hidden]{display:none !important;}
.adm-modal__backdrop{
  position:absolute; inset:0;
  background:rgba(7,27,61,.6);
  animation:rcFade .22s ease;
}
@keyframes rcFade{from{opacity:0}to{opacity:1}}
.adm-modal__dialog{
  position:relative; width:100%; max-width:620px; max-height:92vh;
  background:#fff; border-radius:20px;
  box-shadow:0 30px 80px rgba(7,27,61,.35);
  padding:22px 22px 20px;
  animation:rcPop .28s cubic-bezier(.2,.9,.3,1.2);
  display:flex; flex-direction:column; overflow:hidden;
}
@keyframes rcPop{from{opacity:0; transform:translateY(20px) scale(.96);}to{opacity:1; transform:none;}}
.adm-modal__close{
  position:absolute; top:12px; right:12px;
  width:36px; height:36px; border-radius:50%; border:0;
  background:rgba(11,42,91,.06); color:var(--navy-700); cursor:pointer;
  display:inline-flex; align-items:center; justify-content:center; transition:.18s;
  font-size:20px; line-height:1;
}
.adm-modal__close:hover{background:rgba(212,59,59,.15); color:#b42f2f;}
.adm-modal__head{
  display:flex; align-items:center; gap:12px;
  padding-bottom:14px; margin-bottom:14px; border-bottom:1px dashed var(--line);
}
.adm-modal__icon{
  width:44px; height:44px; border-radius:12px;
  background:linear-gradient(135deg,var(--gold-300),var(--gold-500)); color:#2a1a00;
  display:inline-flex; align-items:center; justify-content:center; flex:none;
}
.adm-modal__head h3{margin:0; font-size:18px; color:var(--navy-900); font-weight:800;}
.adm-modal__head small{display:block; font-size:12px; font-weight:700; color:var(--muted); margin-top:2px;}
.adm-modal__body{overflow-y:auto; overscroll-behavior:contain; padding:0 2px; flex:1;}
.adm-modal__foot{
  display:flex; align-items:center; justify-content:flex-end; gap:10px;
  padding-top:14px; margin-top:14px; border-top:1px dashed var(--line);
}

/* ===== Confirm dialog ===== */
.confirm__icon{
  width:56px; height:56px; border-radius:50%; margin:0 auto 10px;
  background:rgba(212,59,59,.12); color:#b42f2f;
  display:inline-flex; align-items:center; justify-content:center;
}
.confirm__body{text-align:center; padding:10px 4px 4px;}
.confirm__body h4{margin:4px 0 6px; font-size:17px; color:var(--navy-900);}
.confirm__body p{margin:0; font-size:13.5px; color:var(--muted); font-weight:600;}

/* ===== Custom form dropdowns (inside modal fields & detail rows) ===== */
.fld-dd{position:relative;width:100%;}
.fld-dd__btn{
  display:flex; align-items:center; gap:8px;
  width:100%; height:44px; padding:0 38px 0 12px; border-radius:10px;
  border:1.6px solid rgba(11,42,91,.16); background:#fff;
  font:inherit; font-size:14.5px; font-weight:500; color:var(--ink);
  cursor:pointer; transition:border-color .2s, box-shadow .2s; text-align:left;
  overflow:hidden; box-sizing:border-box;
}
.fld-dd__btn:hover{border-color:var(--gold-500); background:#fffdf6;}
.fld-dd.is-open .fld-dd__btn,
.fld-dd__btn:focus{
  outline:none; border-color:var(--gold-500);
  box-shadow:0 0 0 4px rgba(232,163,23,.18);
}
.fld-dd__btn .fld-dd__label{
  flex:1; min-width:0; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
  color:var(--navy-800); font-weight:600;
  display:inline-flex; align-items:center; gap:8px;
  height:100%;
}
.fld-dd__btn .fld-dd__label > svg{flex:none; color:var(--gold-500); width:16px; height:16px;}
.fld-dd__btn .fld-dd__label > span{min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
.fld-dd__logo,
.fld-dd__btn img,
.fld-dd__btn .fld-dd__label img,
.fld-dd__item img{
  width:22px; height:22px; max-width:22px; max-height:22px;
  object-fit:contain; flex:none; display:block;
  padding:2px; box-sizing:border-box;
  border:1px solid var(--line); border-radius:6px; background:#fff;
}
.fld-dd__btn .fld-dd__label.is-placeholder{color:var(--muted); font-weight:500;}
.fld-dd__btn .fld-dd__caret{
  position:absolute; right:12px; top:50%; transform:translateY(-50%);
  color:var(--muted); transition:transform .2s; pointer-events:none;
}
.fld-dd.is-open .fld-dd__btn .fld-dd__caret{transform:translateY(-50%) rotate(180deg); color:var(--gold-500);}
.fld-dd__menu{
  position:absolute; top:calc(100% + 6px); left:0; right:0; z-index:70;
  max-height:280px; overflow-y:scroll; overflow-x:hidden;
  background:#fff; border:1px solid var(--line); border-radius:12px;
  box-shadow:0 14px 36px rgba(7,27,61,.2);
  padding:6px;
  scrollbar-width:none; -ms-overflow-style:none; overscroll-behavior:contain;
}
.fld-dd__menu::-webkit-scrollbar{width:0; height:0; display:none;}
.fld-dd__item{
  display:flex; align-items:center; gap:9px;
  width:100%; padding:9px 10px; border:0; border-radius:9px;
  background:transparent; font:inherit; font-weight:600; font-size:13.5px;
  color:var(--navy-800); text-align:left; cursor:pointer; transition:.15s;
}
.fld-dd__item svg{color:var(--gold-500); flex:none; width:16px; height:16px;}
.fld-dd__item:hover{background:rgba(11,42,91,.06);}
.fld-dd__item.is-active{
  background:linear-gradient(135deg,var(--navy-700),var(--navy-900)); color:#fff;
}
.fld-dd__item.is-active svg{color:var(--gold-400);}

/* smaller variant for detail-row icon picker */
.det-dd{position:relative;}
.det-dd .fld-dd__btn{height:38px; font-size:13px; padding:0 32px 0 10px; border-radius:9px; border:1.2px solid rgba(11,42,91,.16);}
.det-dd .fld-dd__btn .fld-dd__caret{right:10px;}
.det-dd .fld-dd__menu{min-width:180px; max-height:260px;}
.det-dd .fld-dd__item{padding:7px 9px; font-size:13px;}
.det-dd .fld-dd__item svg{width:14px; height:14px;}

/* ===== Detail rows (in form) ===== */
.detail-rows{
  border:1px solid var(--line); border-radius:12px; padding:10px;
  background:#f7f9fd;
}
.detail-row{
  display:grid; grid-template-columns:170px 1fr 38px; gap:8px; margin-bottom:8px; align-items:start;
}
.detail-row:last-child{margin-bottom:0;}
.detail-row input{
  height:38px; padding:0 10px; border:1.2px solid rgba(11,42,91,.16);
  border-radius:9px; background:#fff; font:inherit; font-size:13px; font-weight:600;
  color:var(--navy-800); width:100%; min-width:0; box-sizing:border-box;
}
.detail-row .det-dd{width:100%;}
.detail-row .del-row{
  width:36px; height:38px; border-radius:9px; border:1px solid rgba(212,59,59,.25);
  background:#fff; color:#b42f2f; cursor:pointer; display:inline-flex; align-items:center;
  justify-content:center; transition:.15s;
}
.detail-row .del-row:hover{background:#fee2e2;}
.add-detail{
  margin-top:8px; width:100%; height:36px; border-radius:9px; border:1.6px dashed rgba(11,42,91,.25);
  background:transparent; color:var(--navy-700); font:inherit; font-weight:700; font-size:13px;
  cursor:pointer; transition:.2s;
}
.add-detail:hover{border-color:var(--gold-500); color:var(--gold-600); background:#fffdf6;}

/* ===== Toggle switch (inline) ===== */
.sw{
  position:relative; display:inline-block; width:42px; height:24px; flex:none;
}
.sw input{opacity:0; width:0; height:0;}
.sw__slider{
  position:absolute; inset:0; background:#d2d7e2; border-radius:999px; cursor:pointer;
  transition:.25s;
}
.sw__slider::before{
  content:''; position:absolute; left:3px; top:3px; width:18px; height:18px;
  border-radius:50%; background:#fff; transition:.25s; box-shadow:0 2px 4px rgba(7,27,61,.2);
}
.sw input:checked + .sw__slider{background:linear-gradient(135deg,var(--gold-300),var(--gold-500));}
.sw input:checked + .sw__slider::before{transform:translateX(18px);}

/* ===== Responsive ===== */
@media (max-width:620px){
  .plan-toolbar .search-wrap{flex-basis:100%;}
  .adm-dd{flex:1 1 auto; min-width:0;}
  .adm-dd__btn{width:100%; justify-content:space-between;}
  .adm-dd__menu{position:fixed; left:14px; right:14px;}
  .adm-modal{padding:10px;}
  .adm-modal__dialog{padding:18px 14px 14px; border-radius:18px;}
  .detail-row{grid-template-columns:140px 1fr 36px; gap:6px;}
  .detail-row input{font-size:12.5px; padding:0 8px; height:34px;}
  .det-dd .fld-dd__btn{height:38px; font-size:12.5px;}
  .pg-wrap{justify-content:center;}
  .pg-info{width:100%; text-align:center;}
}
</style>
@endpush

@section('content')

<div class="plan-toolbar">
  <div class="search-wrap">
    <span class="si"><x-icon name="search" :size="15"/></span>
    <input type="text" id="planQ" placeholder="Search plans, operators, plan codes…" value="{{ request('q') }}" autocomplete="off">
  </div>

  {{-- Operator filter --}}
  <div class="adm-dd" data-dd>
    <button type="button" class="adm-dd__btn" data-dd-btn>
      <x-icon name="pin" :size="13"/>
      <span class="adm-dd__label" data-dd-label>All services</span>
      <x-icon name="caret" :size="10" class="adm-dd__caret"/>
    </button>
    <div class="adm-dd__menu" data-dd-menu hidden>
      <button type="button" class="adm-dd__item is-active" data-value="">All services</button>
      @foreach ($services as $s)
        <button type="button" class="adm-dd__item" data-value="{{ $s->id }}">
          @if ($s->logo)
            <img src="{{ $s->logoUrl }}" alt="" onerror="this.style.display='none'">
          @endif
          <span>{{ $s->name }}</span>
        </button>
      @endforeach
    </div>
  </div>

  {{-- Type filter --}}
  <div class="adm-dd" data-dd>
    <button type="button" class="adm-dd__btn" data-dd-btn>
      <x-icon name="grid" :size="13"/>
      <span class="adm-dd__label" data-dd-label>All types</span>
      <x-icon name="caret" :size="10" class="adm-dd__caret"/>
    </button>
    <div class="adm-dd__menu" data-dd-menu hidden>
      <button type="button" class="adm-dd__item is-active" data-value="">All types</button>
      @foreach ([
        'data'=>'Data','combo'=>'Combo','voice'=>'Voice','social'=>'Social',
        'reload'=>'Reload','tv'=>'TV','bill'=>'Bill','utility'=>'Utility','postpaid'=>'Postpaid'
      ] as $k=>$v)
        <button type="button" class="adm-dd__item" data-value="{{ $k }}">
          <x-icon name="{{ $k==='data'?'wifi':($k==='combo'?'grid':($k==='voice'?'phone':($k==='social'?'users':($k==='tv'?'tv-card':($k==='reload'?'bolt':'bill'))))) }}" :size="13"/>
          <span>{{ $v }}</span>
        </button>
      @endforeach
    </div>
  </div>

  {{-- Status filter --}}
  <div class="adm-dd" data-dd>
    <button type="button" class="adm-dd__btn" data-dd-btn>
      <x-icon name="check" :size="13"/>
      <span class="adm-dd__label" data-dd-label>All status</span>
      <x-icon name="caret" :size="10" class="adm-dd__caret"/>
    </button>
    <div class="adm-dd__menu" data-dd-menu hidden>
      <button type="button" class="adm-dd__item is-active" data-value="">All status</button>
      <button type="button" class="adm-dd__item" data-value="active"><span style="color:#1c7a49;">●</span><span>Active</span></button>
      <button type="button" class="adm-dd__item" data-value="inactive"><span style="color:#b42f2f;">●</span><span>Inactive</span></button>
    </div>
  </div>

  <button type="button" class="btn-admin btn-admin--ghost btn-admin--sm" id="resetBtn">
    <x-icon name="x" :size="11"/> Reset
  </button>
  <span class="spacer" style="flex:1;"></span>
  <button type="button" class="btn-admin btn-admin--gold" id="addPlanBtn">
    <x-icon name="plus" :size="14"/> Add Plan
  </button>
</div>

<div class="card">
  <div class="card__head">
    <h3>Plans / Packages <span id="countBadge" class="results-count" style="margin-left:8px;">{{ $plans->total() }} total</span></h3>
    <small style="color:var(--muted);">Real operator plans & denominations shown to customers.</small>
  </div>

  <div class="table-wrap">
    <table class="data-table" id="plansTable">
      <thead>
        <tr>
          <th style="width:180px;">Operator</th>
          <th>Plan</th>
          <th style="width:110px;">Type</th>
          <th style="width:110px;">Amount</th>
          <th style="width:110px;">Validity</th>
          <th style="width:100px;">Code</th>
          <th style="width:90px;">Status</th>
          <th style="width:200px; text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody id="plansTbody">
        @each('admin.plans._row', $plans, 'p')
        @if ($plans->count() === 0)
          <tr><td colspan="8" class="empty">No plans match your filters.</td></tr>
        @endif
      </tbody>
    </table>
  </div>

  {{-- Custom pagination --}}
  <div class="pg-wrap">
    <div class="pg-info">
      Showing {{ $plans->firstItem() ?? 0 }}–{{ $plans->lastItem() ?? 0 }} of {{ $plans->total() }} plans
    </div>
    <div class="pg">
      @if ($plans->onFirstPage())
        <span class="disabled">‹</span>
      @else
        <a href="{{ $plans->previousPageUrl() }}" rel="prev">‹</a>
      @endif

      @foreach ($plans->getUrlRange(1, $plans->lastPage()) as $page => $url)
        @if ($page === $plans->currentPage())
          <span class="active">{{ $page }}</span>
        @elseif (
          $page === 1 ||
          $page === $plans->lastPage() ||
          ($page >= $plans->currentPage() - 2 && $page <= $plans->currentPage() + 2)
        )
          <a href="{{ $url }}">{{ $page }}</a>
        @elseif ($page === $plans->currentPage() - 3 || $page === $plans->currentPage() + 3)
          <span class="pg__dots">…</span>
        @endif
      @endforeach

      @if ($plans->hasMorePages())
        <a href="{{ $plans->nextPageUrl() }}" rel="next">›</a>
      @else
        <span class="disabled">›</span>
      @endif
    </div>
  </div>
</div>

{{-- ===== Add/Edit Plan Modal ===== --}}
<div class="adm-modal" id="planModal" hidden aria-hidden="true">
  <div class="adm-modal__backdrop" data-close></div>
  <div class="adm-modal__dialog" role="dialog" aria-modal="true">
    <button type="button" class="adm-modal__close" data-close aria-label="Close">×</button>
    <div class="adm-modal__head">
      <div class="adm-modal__icon"><x-icon name="gift" :size="20"/></div>
      <div>
        <h3 id="planModalTitle">Add Plan</h3>
        <small id="planModalSub">Create a new plan/package visible to customers.</small>
      </div>
    </div>
    <form id="planForm" class="adm-modal__body" autocomplete="off">
      @csrf
      <input type="hidden" name="_method" id="planMethod" value="POST">
      <input type="hidden" name="id" id="planId">

      <div class="form-grid">
        <div class="field" style="grid-column:1/-1;">
          <label>Service / Operator <span class="req">*</span></label>
          <input type="hidden" name="service_id" id="f_service" value="" required>
          <div class="fld-dd" data-fld-dd data-target="f_service">
            <button type="button" class="fld-dd__btn" data-fld-dd-btn>
              <span class="fld-dd__label is-placeholder" data-fld-dd-label>— Select service —</span>
              <x-icon name="caret" :size="10" class="fld-dd__caret"/>
            </button>
            <div class="fld-dd__menu" data-fld-dd-menu hidden>
              <button type="button" class="fld-dd__item is-active" data-value="" data-label="— Select service —">— Select service —</button>
              @foreach ($services as $s)
                <button type="button" class="fld-dd__item" data-value="{{ $s->id }}" data-label="{{ $s->name }} ({{ ucfirst($s->type) }})">
                  <img class="fld-dd__logo" src="{{ $s->logoUrl }}" alt="" onerror="this.style.display='none'">
                  <span>{{ $s->name }} ({{ ucfirst($s->type) }})</span>
                </button>
              @endforeach
            </div>
          </div>
        </div>

        <div class="field" style="grid-column:1/-1;">
          <label>Plan Name <span class="req">*</span></label>
          <input type="text" name="name" id="f_name" placeholder="e.g. 1.5GB Anytime Data" required>
        </div>

        <div class="field">
          <label>Amount (LKR) <span class="req">*</span></label>
          <input type="number" step="0.01" min="10" name="amount" id="f_amount" required>
        </div>
        <div class="field">
          <label>Type <span class="req">*</span></label>
          <input type="hidden" name="type" id="f_type" value="reload">
          <div class="fld-dd" data-fld-dd data-target="f_type">
            <button type="button" class="fld-dd__btn" data-fld-dd-btn>
              <span class="fld-dd__label" data-fld-dd-label>Reload / Talk-time</span>
              <x-icon name="caret" :size="10" class="fld-dd__caret"/>
            </button>
            <div class="fld-dd__menu" data-fld-dd-menu hidden>
              @php
                $_types = [
                  'reload'   => ['label'=>'Reload / Talk-time', 'icon'=>'bolt'],
                  'data'     => ['label'=>'Data Package', 'icon'=>'wifi'],
                  'voice'    => ['label'=>'Voice / Minutes', 'icon'=>'phone'],
                  'combo'    => ['label'=>'Combo / Blaster', 'icon'=>'grid'],
                  'social'   => ['label'=>'Social Media Pack', 'icon'=>'users'],
                  'tv'       => ['label'=>'TV Subscription', 'icon'=>'tv-card'],
                  'postpaid' => ['label'=>'Postpaid Monthly', 'icon'=>'bill'],
                  'bill'     => ['label'=>'Bill / Wallet / Insurance', 'icon'=>'bill'],
                  'utility'  => ['label'=>'Utility Payment', 'icon'=>'bolt'],
                ];
              @endphp
              @foreach ($_types as $k=>$t)
                <button type="button" class="fld-dd__item {{ $k==='reload'?'is-active':'' }}" data-value="{{ $k }}" data-label="{{ $t['label'] }}">
                  <x-icon name="{{ $t['icon'] }}" :size="13"/>
                  <span>{{ $t['label'] }}</span>
                </button>
              @endforeach
            </div>
          </div>
        </div>

        <div class="field">
          <label>Plan Code (optional)</label>
          <input type="text" name="plan_code" id="f_code" placeholder="Provider code">
        </div>
        <div class="field">
          <label>Validity</label>
          <input type="text" name="validity" id="f_validity" placeholder="e.g. 30 Days">
        </div>

        <div class="field">
          <label>Sort Order</label>
          <input type="number" min="0" name="sort_order" id="f_sort" value="0">
        </div>
        <div class="field" style="display:flex; align-items:center; gap:10px; padding-top:28px;">
          <label class="sw">
            <input type="checkbox" name="is_active" id="f_active" value="1" checked>
            <span class="sw__slider"></span>
          </label>
          <span style="font-weight:700; color:var(--navy-800); font-size:13.5px;">Active (visible to customers)</span>
        </div>

        <div class="field" style="grid-column:1/-1;">
          <label>Description (shown to customers)</label>
          <textarea name="description" id="f_desc" rows="2" placeholder="Short summary of what's included"></textarea>
        </div>

        <div class="field" style="grid-column:1/-1;">
          <label>Inclusions / Plan Details <small style="color:var(--muted); font-weight:600;">(rows shown in the recharge popup)</small></label>
          <div class="detail-rows" id="detailRows">
            {{-- rows added dynamically --}}
          </div>
          <button type="button" class="add-detail" id="addDetailBtn">+ Add detail row</button>
        </div>
      </div>
    </form>
    <div class="adm-modal__foot">
      <button type="button" class="btn-admin btn-admin--ghost" data-close>Cancel</button>
      <button type="button" class="btn-admin btn-admin--gold" id="planSaveBtn">
        <span class="btn-label"><x-icon name="check" :size="13"/> Save Plan</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </div>
  </div>
</div>

{{-- ===== Confirm Delete Modal ===== --}}
<div class="adm-modal" id="confirmModal" hidden aria-hidden="true">
  <div class="adm-modal__backdrop" data-close></div>
  <div class="adm-modal__dialog" role="dialog" aria-modal="true" style="max-width:400px;">
    <button type="button" class="adm-modal__close" data-close aria-label="Close">×</button>
    <div class="adm-modal__body confirm__body">
      <div class="confirm__icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <h4 id="confirmTitle">Delete plan?</h4>
      <p id="confirmMsg">This will permanently remove the plan. This action cannot be undone.</p>
    </div>
    <div class="adm-modal__foot" style="justify-content:center;">
      <button type="button" class="btn-admin btn-admin--ghost" data-close>Cancel</button>
      <button type="button" class="btn-admin btn-admin--danger" id="confirmOkBtn">
        <span class="btn-label"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path></svg> Delete</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
(function(){
  // Icons for detail rows
  var DETAIL_ICONS = [
    {v:'wifi',   l:'Data / Wi-Fi'},
    {v:'bolt',   l:'Credit / Bolt'},
    {v:'phone',  l:'Minutes'},
    {v:'grid',   l:'Combo / Pack'},
    {v:'users',  l:'Social / Apps'},
    {v:'tv-card',l:'TV'},
    {v:'bill',   l:'Bill'},
    {v:'clock',  l:'Validity'},
    {v:'check',  l:'Other'},
  ];

  var CHECK_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
  var TRASH_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path></svg>';

  /* ---------- Scroll lock (event-based; avoids body overflow:hidden which breaks sticky sidebar) ---------- */
  var openModals = [];
  function isScrollable(el){
    if (!el) return false;
    var s = getComputedStyle(el);
    return (s.overflowY === 'auto' || s.overflowY === 'scroll' || s.overflow === 'auto' || s.overflow === 'scroll')
      && el.scrollHeight > el.clientHeight + 2;
  }
  function findScrollableParent(target){
    var n = target;
    while (n && n !== document.body){
      if (isScrollable(n)) return n;
      n = n.parentElement;
    }
    return null;
  }
  function canScroll(el, deltaY){
    // Walk to nearest scrollable ancestor inside a modal; check edge.
    var box = null;
    var n = el;
    while (n && n !== document.body){
      if (n.classList && n.classList.contains('adm-modal__dialog')){ box = n; break; }
      n = n.parentElement;
    }
    var scroller = findScrollableParent(el);
    while (scroller && box && !box.contains(scroller)) scroller = findScrollableParent(scroller.parentElement);
    if (!scroller) return false;
    var atTop = scroller.scrollTop <= 0;
    var atBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 1;
    if (deltaY < 0 && atTop) return false;
    if (deltaY > 0 && atBottom) return false;
    return true;
  }
  function onWheel(e){
    if (openModals.length === 0) return;
    var top = openModals[openModals.length - 1];
    if (top && top.contains(e.target)){
      if (!canScroll(e.target, e.deltaY)) e.preventDefault();
    } else {
      e.preventDefault();
    }
  }
  function onTouchMove(e){
    if (openModals.length === 0) return;
    var top = openModals[openModals.length - 1];
    if (top && top.contains(e.target)){
      // Allow inner scrolling (touchmove has natural edge-bounce guard on mobile)
      return;
    }
    e.preventDefault();
  }
  function onKeyScroll(e){
    var keys = [32, 33, 34, 35, 36, 38, 40]; // space, pgup/pgdn, home/end, up/down
    if (keys.indexOf(e.keyCode) === -1) return;
    if (openModals.length === 0) return;
    var top = openModals[openModals.length - 1];
    if (top && top.contains(e.target)) return;
    e.preventDefault();
  }
  function lockBody(){
    if (openModals.length === 0){
      window.addEventListener('wheel', onWheel, {capture:true, passive:false});
      window.addEventListener('touchmove', onTouchMove, {capture:true, passive:false});
      window.addEventListener('keydown', onKeyScroll, {capture:true});
    }
  }
  function unlockBody(){
    if (openModals.length === 0){
      window.removeEventListener('wheel', onWheel, {capture:true, passive:false});
      window.removeEventListener('touchmove', onTouchMove, {capture:true, passive:false});
      window.removeEventListener('keydown', onKeyScroll, {capture:true});
    }
  }

  /* ---------- Reusable dropdown setup ---------- */
  // Shared menu-position helper
  function positionMenu(dd, btn, menu, opts){
    opts = opts || {};
    var isMobile = window.innerWidth <= 620;
    if (opts.modalBound && isMobile){
      // Inside a modal on mobile: constrain within the dialog bounds
      var dialog = dd.closest('.adm-modal__dialog');
      var dRect = dialog ? dialog.getBoundingClientRect() : {left:14, right:window.innerWidth-14};
      menu.style.position = 'fixed';
      menu.style.left = (dRect.left) + 'px';
      menu.style.right = (window.innerWidth - dRect.right) + 'px';
      var bRect = btn.getBoundingClientRect();
      menu.style.top = Math.min(bRect.bottom + 6, window.innerHeight - 20) + 'px';
      menu.style.maxHeight = Math.max(160, window.innerHeight - bRect.bottom - 30) + 'px';
      return;
    }
    if (isMobile && opts.mobileFixed){
      menu.style.position = 'fixed';
      menu.style.left = '14px'; menu.style.right = '14px';
      menu.style.top = Math.min(btn.getBoundingClientRect().bottom + 6, window.innerHeight - 20) + 'px';
      menu.style.maxHeight = Math.max(180, window.innerHeight - btn.getBoundingClientRect().bottom - 40) + 'px';
      return;
    }
    menu.style.position = 'absolute';
    menu.style.top = 'calc(100% + 6px)';
    menu.style.left = ''; menu.style.right = '';
    var r = dd.getBoundingClientRect();
    var w = menu.offsetWidth || (opts.minWidth || 220);
    if (window.innerWidth - r.right < w && r.left >= w){
      menu.style.left = 'auto'; menu.style.right = '0';
    } else {
      menu.style.left = '0'; menu.style.right = 'auto';
    }
    menu.style.maxHeight = (opts.maxHeight || 320) + 'px';
  }

  // Toolbar [data-dd] dropdowns (All services / All types / All status)
  function setupToolbarDD(dd){
    var btn = dd.querySelector('[data-dd-btn]');
    var menu = dd.querySelector('[data-dd-menu]');
    var label = dd.querySelector('[data-dd-label]');
    var items = menu.querySelectorAll('[data-value]');
    function open(){
      closeAllDD();
      dd.classList.add('is-open'); menu.hidden = false;
      positionMenu(dd, btn, menu, {mobileFixed:true, minWidth:220, maxHeight:320});
    }
    function close(){ dd.classList.remove('is-open'); menu.hidden = true; }
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      // Toggle: if already open, close; otherwise open
      if (dd.classList.contains('is-open')){ close(); } else { open(); }
    });
    items.forEach(function(it){
      it.addEventListener('click', function(e){
        e.stopPropagation();
        items.forEach(function(i){i.classList.remove('is-active');});
        it.classList.add('is-active');
        label.textContent = it.dataset.label || it.textContent.trim();
        close(); applyFilters();
      });
    });
    dd._close = close;
  }
  document.querySelectorAll('[data-dd]').forEach(setupToolbarDD);

  // Form-field [data-fld-dd] custom selects (Service, Type in modal; icon picker in detail rows)
  function setupFieldDD(dd){
    var btn = dd.querySelector('[data-fld-dd-btn]');
    var menu = dd.querySelector('[data-fld-dd-menu]');
    var label = dd.querySelector('[data-fld-dd-label]');
    var targetId = dd.dataset.target;
    var hidden = targetId ? document.getElementById(targetId) : null;
    var items = menu.querySelectorAll('[data-value]');
    var inModal = !!dd.closest('.adm-modal');
    var isDet = dd.classList.contains('det-dd');
    function open(){
      closeAllDD();
      dd.classList.add('is-open'); menu.hidden = false;
      positionMenu(dd, btn, menu, {modalBound:inModal, mobileFixed:false, minWidth: isDet?180:260, maxHeight:280});
    }
    function close(){ dd.classList.remove('is-open'); menu.hidden = true; }
    function setValue(val){
      var it = null;
      items.forEach(function(i){ if ((i.dataset.value||'') === (val==null?'':String(val))) it = i; });
      items.forEach(function(i){i.classList.remove('is-active');});
      if (it){
        it.classList.add('is-active');
        var ic = it.querySelector('svg') || it.querySelector('img');
        var sp = it.querySelector('span');
        var text = sp ? sp.textContent : (it.dataset.label || it.textContent.trim());
        if (ic){
          var icClone = ic.cloneNode(true);
          if (icClone.tagName === 'IMG'){
            icClone.classList.add('fld-dd__logo');
            icClone.removeAttribute('width');
            icClone.removeAttribute('height');
            icClone.style.width = '22px';
            icClone.style.height = '22px';
            icClone.style.maxWidth = '22px';
            icClone.style.maxHeight = '22px';
            icClone.style.objectFit = 'contain';
            icClone.style.flex = 'none';
          }
          label.innerHTML = '';
          label.appendChild(icClone);
          var ts = document.createElement('span');
          ts.textContent = text;
          label.appendChild(ts);
        } else {
          label.textContent = text;
        }
        label.classList.remove('is-placeholder');
      }
      if (hidden) hidden.value = val==null ? '' : val;
    }
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      if (dd.classList.contains('is-open')){ close(); } else { open(); }
    });
    items.forEach(function(it){
      it.addEventListener('click', function(e){
        e.stopPropagation();
        setValue(it.dataset.value);
        close();
        if (hidden){
          // fire change event for any listeners
          hidden.dispatchEvent(new Event('change', {bubbles:true}));
        }
      });
    });
    dd._fldSetValue = setValue;
    dd._close = close;
  }
  function setupAllFieldDDs(root){
    (root || document).querySelectorAll('[data-fld-dd]').forEach(function(dd){
      if (dd._fldInit) return;
      dd._fldInit = true;
      setupFieldDD(dd);
    });
  }
  setupAllFieldDDs();

  function closeAllDD(){
    document.querySelectorAll('[data-dd].is-open, [data-fld-dd].is-open').forEach(function(dd){
      if (dd._close) dd._close();
      else {
        dd.classList.remove('is-open');
        var m = dd.querySelector('[data-dd-menu],[data-fld-dd-menu]');
        if (m) m.hidden = true;
      }
    });
  }
  document.addEventListener('click', closeAllDD);
  window.addEventListener('resize', closeAllDD);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeAllDD(); });

  /* ---------- Filters ---------- */
  var qInp = document.getElementById('planQ');
  var resetBtn = document.getElementById('resetBtn');
  function currentFilter(idx){
    var dds = document.querySelectorAll('[data-dd]');
    var dd = dds[idx]; if(!dd) return '';
    var a = dd.querySelector('.adm-dd__item.is-active');
    return a ? (a.dataset.value || '') : '';
  }
  function applyFilters(){
    var q = (qInp.value||'').trim().toLowerCase();
    var svc = currentFilter(0);
    var tp = currentFilter(1);
    var st = currentFilter(2);
    var visible = 0;
    document.querySelectorAll('#plansTbody tr[data-plan-row]').forEach(function(tr){
      var show = true;
      if (svc && tr.dataset.service !== svc) show = false;
      if (tp && tr.dataset.type !== tp) show = false;
      if (st){
        var active = tr.dataset.active === '1';
        if ((st==='active' && !active) || (st==='inactive' && active)) show = false;
      }
      if (q && tr.dataset.search.indexOf(q) === -1) show = false;
      tr.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    var empty = document.getElementById('emptyRow');
    if (empty) empty.style.display = visible === 0 ? '' : 'none';
  }
  qInp.addEventListener('input', applyFilters);
  resetBtn.addEventListener('click', function(){
    qInp.value = '';
    document.querySelectorAll('[data-dd]').forEach(function(dd){
      var items = dd.querySelectorAll('.adm-dd__item');
      var lbl = dd.querySelector('[data-dd-label]');
      items.forEach(function(i){i.classList.toggle('is-active', !i.dataset.value);});
      var first = dd.querySelector('.adm-dd__item');
      if (first) lbl.textContent = first.textContent.trim();
    });
    applyFilters();
    qInp.focus();
  });

  /* ---------- Modal helpers (event-based scroll lock) ---------- */
  var planModal = document.getElementById('planModal');
  var confirmModal = document.getElementById('confirmModal');

  function openModal(m){
    m.hidden = false;
    m.setAttribute('aria-hidden','false');
    if (openModals.indexOf(m) === -1) openModals.push(m);
    lockBody();
    setTimeout(function(){
      var first = m.querySelector('input:not([type=hidden]),select,textarea,button:not([data-close]):not([disabled])');
      if (first) first.focus();
    }, 80);
  }
  function closeModal(m){
    m.hidden = true;
    m.setAttribute('aria-hidden','true');
    var i = openModals.indexOf(m);
    if (i !== -1) openModals.splice(i, 1);
    unlockBody();
  }
  document.querySelectorAll('[data-close]').forEach(function(el){
    el.addEventListener('click', function(){
      closeModal(el.closest('.adm-modal'));
    });
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape'){
      // Close topmost modal
      if (openModals.length){
        closeModal(openModals[openModals.length - 1]);
      }
    }
  });

  /* ---------- Button loading helper ---------- */
  function setBtnLoading(btn, loading, labelHtml){
    btn.disabled = !!loading;
    btn.classList.toggle('is-loading', loading);
    var sp = btn.querySelector('.btn-spinner');
    if (sp) sp.hidden = !loading;
    if (labelHtml !== undefined && labelHtml !== null){
      var lbl = btn.querySelector('.btn-label');
      if (lbl) lbl.innerHTML = labelHtml;
    }
  }

  function saveLabelHtml(){ return CHECK_ICON + ' Save Plan'; }
  function saveChangesLabelHtml(){ return CHECK_ICON + ' Save Changes'; }
  function deleteLabelHtml(){ return TRASH_ICON + ' Delete'; }

  /* ---------- Add/Edit Plan ---------- */
  var planForm = document.getElementById('planForm');
  var planTitle = document.getElementById('planModalTitle');
  var planSub = document.getElementById('planModalSub');
  var planId = document.getElementById('planId');
  var planMethod = document.getElementById('planMethod');
  var saveBtn = document.getElementById('planSaveBtn');

  function setFieldDD(targetId, value){
    var dd = planForm.querySelector('[data-fld-dd][data-target="'+targetId+'"]');
    if (dd && dd._fldSetValue) dd._fldSetValue(String(value==null?'':value));
  }
  document.getElementById('addPlanBtn').addEventListener('click', function(){
    planForm.reset();
    planId.value = '';
    planMethod.value = 'POST';
    planForm.setAttribute('action', '{{ route('admin.plans.store') }}');
    planTitle.textContent = 'Add Plan';
    planSub.textContent = 'Create a new plan/package visible to customers.';
    document.getElementById('f_active').checked = true;
    document.getElementById('f_sort').value = 0;
    // Reset custom dropdowns to their default
    setFieldDD('f_service', '');
    setFieldDD('f_type', 'reload');
    renderDetailRows([]);
    setBtnLoading(saveBtn, false, saveLabelHtml());
    openModal(planModal);
  });

  document.addEventListener('click', function(e){
    var editBtn = e.target.closest('[data-edit-plan]');
    if (editBtn){
      var data = JSON.parse(editBtn.dataset.plan);
      planForm.reset();
      planId.value = data.id;
      planMethod.value = 'PATCH';
      planForm.setAttribute('action', data.edit_url);
      planTitle.textContent = 'Edit Plan';
      planSub.textContent = 'Update existing plan — changes reflect immediately.';
      document.getElementById('f_name').value = data.name;
      document.getElementById('f_amount').value = data.amount;
      document.getElementById('f_code').value = data.plan_code || '';
      document.getElementById('f_validity').value = data.validity || '';
      document.getElementById('f_sort').value = data.sort_order || 0;
      document.getElementById('f_active').checked = !!data.is_active;
      document.getElementById('f_desc').value = data.description || '';
      // Hidden inputs + custom dropdown labels
      document.getElementById('f_service').value = data.service_id;
      setFieldDD('f_service', data.service_id);
      document.getElementById('f_type').value = data.type || 'reload';
      setFieldDD('f_type', data.type || 'reload');
      renderDetailRows(data.details || []);
      setBtnLoading(saveBtn, false, saveChangesLabelHtml());
      openModal(planModal);
    }
  });

  /* ---------- Detail rows ---------- */
  var detailRows = document.getElementById('detailRows');
  var addDetailBtn = document.getElementById('addDetailBtn');

  var DETAIL_ICON_SVG = {
    'wifi'   : '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>',
    'bolt'   : '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    'phone'  : '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    'grid'   : '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    'users'  : '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'tv-card':'<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>',
    'bill'   : '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
    'clock'  : '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'check'  : '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
  };
  function iconItemsHtml(selected){
    return DETAIL_ICONS.map(function(o){
      var act = o.v === selected ? ' is-active' : '';
      return '<button type="button" class="fld-dd__item'+act+'" data-value="'+o.v+'" data-label="'+o.l+'">'+(DETAIL_ICON_SVG[o.v]||DETAIL_ICON_SVG.bolt)+'<span>'+o.l+'</span></button>';
    }).join('');
  }
  var _detRowUid = 0;
  function addDetailRow(row){
    row = row || {label:'', value:'', icon:'bolt'};
    var sel = row.icon || 'bolt';
    var selObj = DETAIL_ICONS.find(function(o){return o.v===sel;}) || DETAIL_ICONS[1];
    var hidId = 'det_icon_'+(++_detRowUid);
    var div = document.createElement('div');
    div.className = 'detail-row';
    div.innerHTML =
      '<input type="hidden" name="detail_icon[]" id="'+hidId+'" value="'+sel+'">'+
      '<div class="fld-dd det-dd" data-fld-dd data-target="'+hidId+'">'+
        '<button type="button" class="fld-dd__btn" data-fld-dd-btn>'+
          '<span class="fld-dd__label" data-fld-dd-label>'+(DETAIL_ICON_SVG[sel]||DETAIL_ICON_SVG.bolt)+' <span style="margin-left:6px;">'+selObj.l+'</span></span>'+
          '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="fld-dd__caret"><polyline points="6 9 12 15 18 9"></polyline></svg>'+
        '</button>'+
        '<div class="fld-dd__menu" data-fld-dd-menu hidden>'+iconItemsHtml(sel)+'</div>'+
      '</div>'+
      '<div style="display:flex; flex-direction:column; gap:4px;">'+
        '<input type="text" name="detail_label[]" placeholder="Label (e.g. Anytime Data)" value="'+(row.label||'').replace(/"/g,'&quot;')+'" style="height:34px; padding:0 10px; border:1.2px solid rgba(11,42,91,.12); border-radius:7px; font:inherit; font-size:12.5px; font-weight:600;">'+
        '<input type="text" name="detail_value[]" placeholder="Value (e.g. 5 GB, 30 Days)" value="'+(row.value||'').replace(/"/g,'&quot;')+'" style="height:34px; padding:0 10px; border:1.2px solid rgba(11,42,91,.12); border-radius:7px; font:inherit; font-size:12.5px; font-weight:600;">'+
      '</div>'+
      '<button type="button" class="del-row" aria-label="Remove"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';
    detailRows.appendChild(div);
    setupFieldDD(div.querySelector('[data-fld-dd]'));
    div.querySelector('.del-row').addEventListener('click', function(){ div.remove(); });
  }
  function renderDetailRows(rows){
    detailRows.innerHTML = '';
    if (rows.length === 0){ addDetailRow(); return; }
    rows.forEach(function(r){ addDetailRow(r); });
  }
  addDetailBtn.addEventListener('click', function(){ addDetailRow(); });

  /* ---------- Save Plan ---------- */
  saveBtn.addEventListener('click', function(){
    // Manually validate custom dropdowns (hidden inputs don't trigger native required)
    var svc = document.getElementById('f_service').value;
    if (!svc){
      if (window.toast) window.toast('Please select a service / operator.', 'error');
      var svcDD = planForm.querySelector('[data-fld-dd][data-target="f_service"] .fld-dd__btn');
      if (svcDD) svcDD.focus();
      return;
    }
    if (!planForm.checkValidity()){ planForm.reportValidity(); return; }
    var isEdit = !!planId.value;
    var fd = new FormData(planForm);
    fd.append('is_active', document.getElementById('f_active').checked ? '1' : '0');
    setBtnLoading(saveBtn, true);
    fetch(planForm.getAttribute('action'), {
      method: 'POST',
      body: fd,
      headers: {'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
      credentials: 'same-origin',
    })
    .then(function(r){ return r.json().then(function(d){return {ok:r.ok, data:d};}); })
    .then(function(res){
      if (!res.ok || !res.data.ok) throw new Error((res.data && res.data.message) || 'Save failed.');
      if (window.toast) window.toast(res.data.message, 'success');
      upsertRow(res.data.plan);
      closeModal(planModal);
    })
    .catch(function(err){
      if (window.toast) window.toast(err.message || 'Save failed.', 'error');
      else alert(err.message);
    })
    .finally(function(){ setBtnLoading(saveBtn, false, isEdit ? saveChangesLabelHtml() : saveLabelHtml()); });
  });

  function upsertRow(p){
    var tbody = document.getElementById('plansTbody');
    var existing = document.getElementById('plan-row-'+p.id);
    var empty = document.getElementById('emptyRow');
    if (empty) empty.remove();
    var html = buildRowHtml(p);
    if (existing){
      existing.outerHTML = html;
    } else {
      tbody.insertAdjacentHTML('afterbegin', html);
    }
    updateCount();
  }

  function buildRowHtml(p){
    var activeLabel = p.is_active ? 'Active' : 'Inactive';
    var activeClass = p.is_active ? 'success' : 'failed';
    var btnLabel = p.is_active ? 'Disable' : 'Enable';
    var btnIcon = p.is_active
      ? '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"></rect><line x1="6" y1="9" x2="18" y2="9"></line><line x1="18" y1="4" x2="18" y2="16"></line><line x1="14" y1="16" x2="22" y2="16"></line></svg>'
      : '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    var editIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>';
    return '<tr id="plan-row-'+p.id+'" data-plan-row data-service="'+p.service_id+'" data-type="'+p.type+'" data-active="'+(p.is_active?1:0)+'" data-search="'+(p.name+' '+(p.plan_code||'')+' '+p.service_name+' '+p.amount).toLowerCase()+'">'+
      '<td><div class="op-cell"><img src="'+p.service_logo+'" alt="" onerror="this.style.display=\'none\'"><b>'+escapeHtml(p.service_name)+'</b></div></td>'+
      '<td><b>'+escapeHtml(p.name)+'</b>'+(p.description?'<br><small style="color:var(--muted);">'+escapeHtml(p.description)+'</small>':'')+'</td>'+
      '<td><span class="pill pill--'+p.type_color+'">'+p.type_label+'</span></td>'+
      '<td><b>LKR '+(+p.amount).toLocaleString('en-LK',{minimumFractionDigits:2, maximumFractionDigits:2})+'</b></td>'+
      '<td>'+(p.validity ? escapeHtml(p.validity) : '—')+'</td>'+
      '<td>'+(p.plan_code ? '<code style="font-size:12px;">'+escapeHtml(p.plan_code)+'</code>' : '—')+'</td>'+
      '<td><span class="pill pill--'+activeClass+'">'+activeLabel+'</span></td>'+
      '<td><div class="row-actions">'+
        '<button type="button" class="btn-admin btn-admin--ghost btn-admin--sm" data-edit-plan data-plan=\''+JSON.stringify(p).replace(/'/g,'&#39;')+'\'>'+editIcon+' Edit</button>'+
        '<form method="POST" action="'+p.toggle_url+'" data-plan-toggle style="display:inline;">@csrf<button class="btn-admin btn-admin--ghost btn-admin--sm" type="submit"><span class="btn-label">'+btnIcon+' '+btnLabel+'</span><span class="btn-spinner" hidden></span></button></form>'+
        '<button type="button" class="btn-admin btn-admin--danger btn-admin--sm" data-del-plan="'+p.id+'" data-name="'+escapeHtml(p.name)+'" data-url="'+p.delete_url+'">'+TRASH_ICON+' Delete</button>'+
      '</div></td>'+
    '</tr>';
  }

  function escapeHtml(s){
    if (s===null||s===undefined) return '';
    return String(s).replace(/[&<>"']/g, function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
  }

  function updateCount(){
    var total = document.querySelectorAll('#plansTbody tr[data-plan-row]').length;
    var vis = 0;
    document.querySelectorAll('#plansTbody tr[data-plan-row]').forEach(function(tr){
      if (tr.style.display !== 'none') vis++;
    });
    var badge = document.getElementById('countBadge');
    if (badge) badge.textContent = total + ' total' + (vis !== total ? ' · '+vis+' shown' : '');
  }

  /* ---------- Delete Plan ---------- */
  var confirmOk = document.getElementById('confirmOkBtn');
  var confirmTitle = document.getElementById('confirmTitle');
  var confirmMsg = document.getElementById('confirmMsg');
  var pendingDeleteUrl = null;
  var pendingDeleteId = null;

  document.addEventListener('click', function(e){
    var del = e.target.closest('[data-del-plan]');
    if (!del) return;
    pendingDeleteUrl = del.dataset.url;
    pendingDeleteId = del.dataset.delPlan;
    confirmTitle.textContent = 'Delete "'+del.dataset.name+'"?';
    confirmMsg.textContent = 'This will permanently remove the plan. Customers will no longer see it. This action cannot be undone.';
    setBtnLoading(confirmOk, false, deleteLabelHtml());
    openModal(confirmModal);
  });
  confirmOk.addEventListener('click', function(){
    if (!pendingDeleteUrl) return;
    setBtnLoading(confirmOk, true);
    var fd = new FormData();
    fd.append('_method', 'DELETE');
    fd.append('_token', '{{ csrf_token() }}');
    fetch(pendingDeleteUrl, {
      method:'POST', body:fd,
      headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
      credentials:'same-origin',
    })
    .then(function(r){return r.json().then(function(d){return {ok:r.ok,data:d};});})
    .then(function(res){
      if (!res.ok||!res.data.ok) throw new Error((res.data&&res.data.message)||'Delete failed.');
      if (window.toast) window.toast(res.data.message, 'success');
      var row = document.getElementById('plan-row-'+pendingDeleteId);
      if (row) row.remove();
      pendingDeleteUrl = null; pendingDeleteId = null;
      updateCount();
      closeModal(confirmModal);
    })
    .catch(function(err){
      if (window.toast) window.toast(err.message||'Delete failed.','error');
      else alert(err.message);
    })
    .finally(function(){ setBtnLoading(confirmOk, false, deleteLabelHtml()); });
  });

  /* ---------- Toggle active (inline) — uses capture so it fires BEFORE global [data-ajax] handler in landing.js ---------- */
  document.addEventListener('submit', function(e){
    var form = e.target;
    if (!form || !form.matches('form[data-plan-toggle]')) return;
    e.preventDefault();
    e.stopImmediatePropagation(); // prevent the global [data-ajax] handler from taking over
    if (form.dataset.busy === '1') return;
    form.dataset.busy = '1';
    var btn = form.querySelector('button[type=submit]');
    if (btn){ btn.disabled = true; btn.classList.add('is-loading'); var sp = btn.querySelector('.btn-spinner'); if(sp) sp.hidden = false; }
    fetch(form.action, {
      method:'POST', body:new FormData(form),
      headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
      credentials:'same-origin',
    })
    .then(function(r){return r.json().then(function(d){return {ok:r.ok,data:d};});})
    .then(function(res){
      if (!res.ok||!res.data.ok) throw new Error((res.data&&res.data.message)||'Action failed.');
      if (window.toast) window.toast(res.data.message, 'success');
      var row = form.closest('tr[data-plan-row]');
      if (row){
        // Rebuild the whole row so pill color + button label/icon stay in sync.
        var planData = JSON.parse(row.querySelector('[data-edit-plan]').dataset.plan);
        planData.is_active = !!res.data.is_active;
        upsertRow(planData);
      }
    })
    .catch(function(err){
      if (window.toast) window.toast(err.message||'Action failed.','error');
      else alert(err.message);
    })
    .finally(function(){
      form.dataset.busy = '0';
      if (btn){ btn.disabled = false; btn.classList.remove('is-loading'); var sp = btn.querySelector('.btn-spinner'); if(sp) sp.hidden = true; }
    });
  }, true); // capture: true

  updateCount();
})();
</script>
@endpush
