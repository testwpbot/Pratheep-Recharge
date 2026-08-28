@extends('layouts.dashboard')
@section('title', 'Plans & Rates')

@section('content')

<div class="card">
  <div class="card__head">
    <h3>Plans & Rates</h3>
    <span class="results-count" id="resultsCount">—</span>
  </div>

  @if ($visibleCategories->isEmpty())
    <div style="text-align:center; padding:40px; color:var(--muted);">
      No plans available yet.
    </div>
  @else

    {{-- ====== TOOLBAR: SEARCH + FILTERS ====== --}}
    <div class="plan-toolbar">
      {{-- Search --}}
      <div class="plan-search">
        <x-icon name="search" :size="16"/>
        <input type="text" id="planSearch" placeholder="Search plans, operators, amounts…" autocomplete="off">
        <button type="button" id="planSearchClear" class="plan-search__clear" aria-label="Clear" hidden>
          <x-icon name="x" :size="13"/>
        </button>
      </div>

      {{-- Custom dropdown: Operator --}}
      <div class="plan-dd" data-plan-dd>
        <button type="button" class="plan-dd__btn" data-plan-dd-btn>
          <x-icon name="pin" :size="14"/>
          <span class="plan-dd__label" data-plan-dd-label>All Operators</span>
          <x-icon name="caret" :size="11" class="plan-dd__caret"/>
        </button>
        <div class="plan-dd__menu" data-plan-dd-menu hidden>
          <button type="button" class="plan-dd__item is-active" data-value="">All Operators</button>
          @foreach ($visibleCategories as $cat)
            <div class="plan-dd__group">{{ $cat->name }}</div>
            @foreach ($cat->groups as $g)
              <button type="button" class="plan-dd__item" data-value="{{ $g->key }}">
                @if ($g->logo)
                  <img src="{{ asset($g->logo) }}" alt="" onerror="this.style.display='none'">
                @endif
                <span>{{ $g->label }}{{ !empty($g->tag) ? ' · ' . $g->tag : '' }}</span>
              </button>
            @endforeach
          @endforeach
        </div>
      </div>

      {{-- Custom dropdown: Plan Type --}}
      <div class="plan-dd" data-plan-dd>
        <button type="button" class="plan-dd__btn" data-plan-dd-btn>
          <x-icon name="grid" :size="14"/>
          <span class="plan-dd__label" data-plan-dd-label>All Types</span>
          <x-icon name="caret" :size="11" class="plan-dd__caret"/>
        </button>
        <div class="plan-dd__menu" data-plan-dd-menu hidden>
          <button type="button" class="plan-dd__item is-active" data-value="">All Types</button>
          <button type="button" class="plan-dd__item" data-value="data"><x-icon name="wifi" :size="13"/><span>Data</span></button>
          <button type="button" class="plan-dd__item" data-value="combo"><x-icon name="grid" :size="13"/><span>Combo</span></button>
          <button type="button" class="plan-dd__item" data-value="voice"><x-icon name="phone" :size="13"/><span>Voice</span></button>
          <button type="button" class="plan-dd__item" data-value="social"><x-icon name="users" :size="13"/><span>Social</span></button>
          <button type="button" class="plan-dd__item" data-value="reload"><x-icon name="bolt" :size="13"/><span>Reload</span></button>
          <button type="button" class="plan-dd__item" data-value="tv"><x-icon name="tv-card" :size="13"/><span>TV</span></button>
          <button type="button" class="plan-dd__item" data-value="bill"><x-icon name="bill" :size="13"/><span>Bill</span></button>
        </div>
      </div>

      {{-- Reset --}}
      <button type="button" class="btn-admin btn-admin--ghost btn-admin--sm" id="planReset">
        <x-icon name="x" :size="12"/> Reset
      </button>
    </div>

    {{-- ====== CATEGORY TABS ====== --}}
    <div class="cat-tabs" role="tablist" id="planCatTabs">
      @foreach ($visibleCategories as $cat)
        @php
          // Count how many things a customer can actually click/use in this tab:
          // prepaid plans + bill-payment CTAs (bill-only groups count as 1 each,
          // groups that have a plan list AND a bill CTA count the bill CTA as +1).
          $planCount = $cat->groups->sum(fn($g) => $g->planCount);
          $billCount = $cat->groups->sum(function($g){
            if ($g->is_bill_only) return 1;
            // Plan-bearing group that also has a bill-pay CTA (e.g. Dialog TV postpaid)
            return ($g->billServices && $g->billServices->count()) ? 1 : 0;
          });
          $tabCount = $planCount + $billCount;
        @endphp
        <button type="button"
                role="tab"
                class="cat-tab {{ $loop->first ? 'active' : '' }}"
                data-cat-slug="{{ $cat->slug }}"
                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
          {{ $cat->name }}
          <em style="font-style:normal; font-size:10px; font-weight:800; background:rgba(255,255,255,.2); padding:1px 7px; border-radius:999px; margin-left:4px;">{{ $tabCount }}</em>
        </button>
      @endforeach
    </div>

    <div class="kind-tabs" id="mobileKindTabs" role="tablist" aria-label="Prepaid or Postpaid"
         @if(($visibleCategories->first()->slug ?? '') !== 'mobile') hidden @endif>
      <button type="button" class="kind-tab active" data-kind="" aria-selected="true">All</button>
      <button type="button" class="kind-tab" data-kind="prepaid" aria-selected="false">Prepaid</button>
      <button type="button" class="kind-tab" data-kind="postpaid" aria-selected="false">Postpaid</button>
    </div>

    <div class="plan-panels">
      @foreach ($visibleCategories as $cat)
        <div class="plan-panel {{ $loop->first ? 'is-active' : '' }}"
             id="plan-panel-{{ $cat->slug }}"
             data-cat-panel="{{ $cat->slug }}">

          @foreach ($cat->groups as $g)
            @php
              $hasPrepaidLine = !$g->is_bill_only && ($g->planCount > 0 || $g->primary);
              $hasPostpaidLine = ($g->billServices && $g->billServices->isNotEmpty())
                || ($g->is_bill_only && $g->primary)
                || (!empty($g->tag) && strtolower($g->tag) === 'postpaid');
            @endphp
            <div class="op-block @if(!empty($g->tag) && strtolower($g->tag) === 'postpaid') op-block--postpaid @endif"
                 id="op-{{ $g->key }}" data-op data-op-name="{{ strtolower($g->label) }}"
                 data-op-key="{{ $g->key }}"
                 data-line-prepaid="{{ $hasPrepaidLine ? '1' : '0' }}"
                 data-line-postpaid="{{ $hasPostpaidLine ? '1' : '0' }}">

              {{-- Operator header --}}
              <div class="op-block__head">
                <div class="op-block__title">
                  <img src="{{ $g->logo ? asset($g->logo) : asset('assets/logo-mark.png') }}"
                       alt="{{ $g->label }}" class="op-block__logo"
                       onerror="this.src='{{ asset('assets/logo-mark.png') }}'">
                  <div>
                    <h4 style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                      {{ $g->label }}
                      @if (!empty($g->tag))
                        @if (strtolower($g->tag) === 'postpaid')
                          <span class="service-tag service-tag--postpaid">
                            <x-icon name="postpaid-dr" :size="10"/> Postpaid
                          </span>
                        @else
                          <span class="service-tag service-tag--prepaid">
                            <x-icon name="bolt-dr" :size="10"/> {{ $g->tag }}
                          </span>
                        @endif
                      @endif
                    </h4>
                    <small data-op-count>
                      @if ($g->is_bill_only)
                        Bill payment service
                      @else
                        {{ $g->planCount }} plans available
                      @endif
                    </small>
                  </div>
                </div>
              </div>

              @if (!$g->is_bill_only && $g->plansGrouped->isNotEmpty())
                {{-- Plan type sub-tabs: ALWAYS render when there are plans,
                     even if only one type exists, so the JS activate-first-tab logic
                     has a real DOM target to activate (otherwise single-type groups
                     like HBB routers end up with .type-panel{display:none} never overridden).
                     Visually hide the tabs when there's only one group so it stays clean. --}}
                <div class="type-tabs type-tabs--{{ $g->plansGrouped->count() === 1 ? 'single' : 'multi' }}"
                     role="tablist" data-type-tabs data-kind-part="prepaid" @if($g->plansGrouped->count() === 1) style="display:none" @endif>
                  @foreach ($g->plansGrouped as $grp)
                    <button type="button"
                            role="tab"
                            class="type-tab @if($loop->first) active @endif"
                            data-group="{{ $g->key }}"
                            data-type="{{ $grp['type'] }}">
                      <x-icon name="{{ $grp['icon'] }}" :size="13"/>
                      {{ $grp['label'] }}
                      <em>{{ $grp['items']->count() }}</em>
                    </button>
                  @endforeach
                </div>

                {{-- Plan grids per type --}}
                @foreach ($g->plansGrouped as $grp)
                  <div class="type-panel @if($loop->first) is-active @endif"
                       data-type-panel="{{ $g->key }}-{{ $grp['type'] }}"
                       data-type="{{ $grp['type'] }}"
                       data-kind-part="prepaid">
                    <div class="plan-grid">
                      @foreach ($grp['items'] as $p)
                        @php
                          $cb = $p->cashback();
                          // Controller tags each plan with its own service id
                          // (other_ops plans route through their own op_code, not primary).
                          $routeSvcId = $p->route_service_id ?? ($g->primary ? $g->primary->id : null);
                          $searchText = strtolower($p->name . ' ' . $p->amount . ' ' . $g->label . ' ' . ($g->tag ?? '') . ' ' . $grp['label']);
                          $metaDetails = $p->meta['details'] ?? [];
                        @endphp
                        @if ($routeSvcId)
                        <button type="button"
                           class="plan-card"
                           data-plan-card
                           data-op-key="{{ $g->key }}"
                           data-type="{{ $grp['type'] }}"
                           data-search="{{ $searchText }}"
                           data-service-id="{{ $routeSvcId }}"
                           data-amount="{{ $p->amount }}"
                           data-name="{{ $p->name }}"
                           data-validity="{{ $p->validity ?? $grp['label'] . ' plan' }}"
                           data-logo="{{ $g->logo ? asset($g->logo) : asset('assets/logo-mark.png') }}"
                           data-op-name="{{ $g->label }}{{ !empty($g->tag) ? ' ' . $g->tag : '' }}"
                           data-hide-notify="{{ ($cat->slug === 'mobile' || strtolower((string) ($g->tag ?? '')) === 'postpaid') ? '1' : '0' }}"
                           data-cb="{{ number_format($cb, 2) }}"
                           data-details="{{ json_encode($metaDetails, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) }}">
                          <img src="{{ $g->logo ? asset($g->logo) : asset('assets/logo-mark.png') }}"
                               alt=""
                               onerror="this.src='{{ asset('assets/logo-mark.png') }}'">
                          <div class="plan-card__body">
                            <b>LKR {{ number_format($p->amount, 0) }}</b>
                            <span class="plan-card__name">{{ $p->name }}</span>
                            <small>
                              @if ($p->validity)
                                <x-icon name="clock" :size="11"/> {{ $p->validity }}
                              @else
                                {{ $grp['label'] }} plan
                              @endif
                            </small>
                          </div>
                        </button>
                        @endif
                      @endforeach
                    </div>
                  </div>
                @endforeach
              @endif

              {{-- Footer actions: custom amount + bill-payment CTAs (all open same modal) --}}
              <div class="op-block__foot">
                @if ($g->primary && !$g->is_bill_only)
                  <button type="button"
                     class="btn-admin btn-admin--ghost btn-admin--sm"
                     data-kind-part="prepaid"
                     data-rc-custom
                     data-service-id="{{ $g->primary->id }}"
                     data-logo="{{ $g->logo ? asset($g->logo) : asset('assets/logo-mark.png') }}"
                     data-op-name="{{ $g->label }}{{ !empty($g->tag) ? ' ' . $g->tag : '' }}"
                     data-hide-notify="{{ ($cat->slug === 'mobile' || strtolower((string) ($g->tag ?? '')) === 'postpaid') ? '1' : '0' }}"
                     data-mode="reload">
                    <x-icon name="bolt-nav" :size="13"/> Custom amount
                  </button>
                @endif
                @if ($g->billServices && $g->billServices->isNotEmpty())
                  @foreach ($g->billServices as $billSvc)
                    <button type="button"
                       class="btn-admin btn-admin--primary btn-admin--sm"
                       data-kind-part="postpaid"
                       data-rc-custom
                       data-service-id="{{ $billSvc->id }}"
                       data-logo="{{ $g->logo ? asset($g->logo) : asset('assets/logo-mark.png') }}"
                       data-op-name="{{ $g->bill_label ?? ('Pay ' . $billSvc->name) }}"
                       data-hide-notify="{{ ($cat->slug === 'mobile' || strtolower((string) $billSvc->type) === 'postpaid') ? '1' : '0' }}"
                       data-mode="bill">
                      <x-icon name="bill" :size="13"/>
                      {{ $g->bill_label ?? ('Pay ' . $billSvc->name) }}
                    </button>
                  @endforeach
                @endif
                @if ($g->is_bill_only && $g->primary)
                  {{-- Bill-only groups (CEB, LECO, Water, insurance, wallets, TV Lanka, etc.) --}}
                  <button type="button"
                     class="btn-admin btn-admin--primary btn-admin--sm"
                     data-kind-part="postpaid"
                     data-rc-custom
                     data-service-id="{{ $g->primary->id }}"
                     data-logo="{{ $g->logo ? asset($g->logo) : asset('assets/logo-mark.png') }}"
                     data-op-name="{{ $g->bill_label ?? ('Pay ' . $g->label) }}"
                     data-hide-notify="{{ ($cat->slug === 'mobile' || strtolower((string) ($g->primary->type ?? '')) === 'postpaid' || strtolower((string) ($g->tag ?? '')) === 'postpaid') ? '1' : '0' }}"
                     data-mode="bill">
                    <x-icon name="bill" :size="13"/>
                    {{ $g->bill_label ?? ('Pay ' . $g->label) }}
                  </button>
                @elseif ($g->is_bill_only && (!$g->billServices || $g->billServices->isEmpty()))
                  <p class="op-block__tip">
                    <x-icon name="bill" :size="13"/>
                    This bill payment service is coming soon.
                  </p>
                @endif
              </div>

              {{-- No-match hidden state per block --}}
              <div class="plan-no-match" data-no-match hidden>
                <x-icon name="search" :size="18"/>
                <span>No matching plans in {{ $g->label }}{{ !empty($g->tag) ? ' ' . $g->tag : '' }}.</span>
              </div>

            </div>
          @endforeach

          {{-- Empty panel (if all blocks hidden by filter) --}}
          <div class="plan-no-match plan-no-match--panel" data-panel-empty hidden>
            <x-icon name="search" :size="24"/>
            <h4>No plans match your search</h4>
            <p>Try a different keyword, operator, or plan type.</p>
          </div>

        </div>
      @endforeach
    </div>

  @endif
</div>

{{-- ====== QUICK RECHARGE MODAL (outside .card so position:fixed isn't
     trapped by the card's transform/overflow from the page-reveal animation) ====== --}}
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
        <div class="field" id="rcNotifyField">
          <label>Notify Number <small style="color:var(--muted);font-weight:600;">(optional)</small></label>
          <input type="tel" name="notify_number" id="rcNotify" placeholder="Same as above if blank">
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

    {{-- Custom/bill hint (shown in place of the plan card for Custom amount / Bill Pay) --}}
    <div class="rc-modal__hint" id="rcHint" hidden>
      <div class="rc-modal__hint-ic" id="rcHintIc"></div>
      <p id="rcHintText"></p>
    </div>

    {{-- Generating receipt state (shown between success and final buttons) --}}
    <div class="rc-modal__generating" id="rcGenerating" hidden>
      <div class="rc-modal__success-icon rc-modal__success-icon--pending"><x-icon name="clock" :size="28"/></div>
      <h4>Generating your receipt…</h4>
      <p>Please wait while we prepare your receipt.</p>
    </div>

    {{-- Success state (swaps in after successful order) --}}
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

@endsection

@push('styles')
<style>
/* Hidden attribute must always win — many components set display:flex/block
   in their base rule, which otherwise overrides the UA [hidden]{display:none}. */
[hidden]{display:none !important;}

/* Scroll lock when modal is open.
   We don't touch overflow/position on body/html because that resets
   scrollY and breaks position:sticky (the sidebar would jump).
   Instead we prevent wheel / touchmove events from reaching the page
   while the modal is open — scroll position is preserved untouched. */

.results-count{
  font-size:12px; font-weight:700; color:var(--muted);
  background:#f7f9fd; padding:5px 11px; border-radius:999px;
  letter-spacing:.04em;
}

/* ======== toolbar: search + dropdowns ======== */
.plan-toolbar{
  display:flex; gap:10px; align-items:center; flex-wrap:wrap;
  margin-bottom:18px;
}
.plan-search{
  position:relative; flex:1 1 260px; min-width:220px;
  display:flex; align-items:center;
  height:44px; padding:0 14px 0 42px;
  background:#fff; border:1.6px solid rgba(11,42,91,.16);
  border-radius:12px; transition:.2s;
}
.plan-search:focus-within{
  border-color:var(--gold-500); box-shadow:0 0 0 4px rgba(232,163,23,.18);
}
.plan-search svg{position:absolute; left:14px; color:var(--muted); pointer-events:none;}
.plan-search input{
  flex:1; height:100%; border:0; background:transparent;
  font:inherit; font-size:14px; font-weight:600; color:var(--ink); outline:none;
}
.plan-search input::placeholder{color:var(--muted); font-weight:500;}
.plan-search__clear{
  border:0; background:rgba(11,42,91,.08); color:var(--navy-700);
  width:24px; height:24px; border-radius:50%;
  display:inline-flex; align-items:center; justify-content:center;
  cursor:pointer; transition:.18s; flex:none;
}
.plan-search__clear:hover{background:rgba(212,59,59,.15); color:#b42f2f;}

/* custom dropdown */
.plan-dd{position:relative;}
.plan-dd__btn{
  display:inline-flex; align-items:center; gap:8px;
  height:44px; padding:0 14px; border-radius:12px;
  border:1.6px solid rgba(11,42,91,.16); background:#fff;
  font:inherit; font-weight:700; font-size:13.5px; color:var(--navy-800);
  cursor:pointer; transition:.2s; white-space:nowrap;
}
.plan-dd__btn:hover{border-color:var(--gold-500); background:#fffdf6;}
.plan-dd.is-open .plan-dd__btn{
  border-color:var(--gold-500); box-shadow:0 0 0 4px rgba(232,163,23,.18);
}
.plan-dd__label{max-width:180px; overflow:hidden; text-overflow:ellipsis;}
.plan-dd__caret{color:var(--muted); transition:transform .2s;}
.plan-dd.is-open .plan-dd__caret{transform:rotate(180deg); color:var(--gold-500);}

.plan-dd__menu{
  position:absolute; top:calc(100% + 6px); left:0; right:auto; z-index:50;
  min-width:260px; max-height:360px; overflow-y:scroll; overflow-x:hidden;
  background:#fff; border:1px solid var(--line); border-radius:14px;
  box-shadow:0 16px 40px rgba(7,27,61,.22);
  padding:8px;
  /* hide scrollbar across browsers, keep scroll functional */
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE / old Edge */
  overscroll-behavior: contain;
}
.plan-dd__menu::-webkit-scrollbar{width:0; height:0; display:none;} /* Chrome / Safari */

.plan-dd__group{
  font-size:10.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase;
  color:var(--muted); padding:10px 10px 4px;
}
.plan-dd__item{
  display:flex; align-items:center; gap:9px;
  width:100%; padding:8px 10px; border:0; border-radius:9px;
  background:transparent; font:inherit; font-weight:600; font-size:13.5px;
  color:var(--navy-800); text-align:left; cursor:pointer; transition:.15s;
}
.plan-dd__item img{width:20px; height:20px; object-fit:contain; flex:none;}
.plan-dd__item svg{color:var(--gold-500); flex:none;}
.plan-dd__item:hover{background:rgba(11,42,91,.06);}
.plan-dd__item.is-active{
  background:linear-gradient(135deg,var(--navy-700),var(--navy-900)); color:#fff;
}
.plan-dd__item.is-active svg{color:var(--gold-400);}

/* ======== panels / blocks / tabs ======== */
.plan-panels{position:relative;}
.plan-panel{
  opacity:0; transform:translateY(8px);
  position:absolute; inset:0; pointer-events:none; visibility:hidden;
  transition:opacity .28s ease, transform .28s ease;
}
.plan-panel.is-active{
  opacity:1; transform:none;
  position:relative; pointer-events:auto; visibility:visible;
}
.plan-panel.is-leaving{opacity:0; transform:translateY(-6px);}

.op-block{
  background:#f7f9fd; border:1px solid var(--line); border-radius:16px;
  padding:18px 20px; margin-bottom:16px;
  transition:.25s;
}
.op-block:last-child{margin-bottom:0;}
.op-block.is-hidden{display:none;}

.op-block__head{
  display:flex; align-items:center; justify-content:space-between;
  gap:14px; flex-wrap:wrap;
  padding-bottom:14px; margin-bottom:14px;
  border-bottom:1px dashed var(--line);
}
.op-block__title{display:flex; align-items:center; gap:12px; min-width:0;}
.op-block__logo{
  width:44px; height:44px; object-fit:contain;
  padding:5px; border:1px solid var(--line); border-radius:10px;
  background:#fff; box-shadow:0 2px 6px rgba(7,27,61,.06);
}
.op-block__title h4{
  margin:0; font-size:16px; font-weight:800; color:var(--navy-900); letter-spacing:-.01em;
}
.op-block__title small{
  display:block; font-size:12px; font-weight:700; color:var(--muted);
  text-transform:uppercase; letter-spacing:.06em; margin-top:2px;
}
.op-block__std{
  font-size:12px; font-weight:700; color:var(--muted);
  background:#fff; border:1px solid var(--line);
  padding:5px 11px; border-radius:999px; white-space:nowrap;
}

/* service-type tags (Prepaid / Postpaid) */
.service-tag{
  display:inline-flex; align-items:center; gap:4px;
  font-size:10.5px; font-weight:800; letter-spacing:.06em;
  padding:3px 9px; border-radius:999px; text-transform:uppercase;
}
.service-tag--prepaid{background:rgba(232,163,23,.14); color:#a86f00;}
.service-tag--prepaid svg{color:#c88a10;}
.service-tag--postpaid{background:rgba(192,57,43,.1); color:#a22d22;}
.service-tag--postpaid svg{color:#c0392b;}
.op-block--postpaid{
  background:linear-gradient(180deg,#fff 0%,#fff8f7 100%);
  border-color:#f3cfc9;
}

/* type sub-tabs */
.type-tabs{
  display:flex; gap:6px; flex-wrap:wrap;
  padding:6px; background:#fff; border:1px solid var(--line);
  border-radius:12px; margin-bottom:16px;
  width:100%; box-sizing:border-box;
}
.type-tab{
  display:inline-flex; align-items:center; justify-content:center; gap:6px;
  padding:8px 14px; border-radius:9px; border:0;
  background:transparent; color:var(--navy-800);
  font:inherit; font-weight:700; font-size:13px;
  cursor:pointer; transition:.18s; white-space:nowrap;
}
.type-tab svg{color:var(--muted); flex:none; transition:color .18s;}
.type-tab em{
  font-style:normal; font-size:11px; font-weight:800;
  background:rgba(11,42,91,.08); padding:1px 7px; border-radius:999px; color:var(--navy-700);
}
.type-tab:hover{background:rgba(11,42,91,.05);}
.type-tab.is-hidden{display:none;}
.type-tab.active{
  background:linear-gradient(135deg,var(--navy-700),var(--navy-900));
  color:#fff; box-shadow:0 4px 10px rgba(7,27,61,.22);
}
.type-tab.active svg{color:var(--gold-400);}
.type-tab.active em{background:rgba(232,163,23,.3); color:#fff;}

.kind-tabs{
  display:flex; gap:8px; flex-wrap:wrap;
  padding:6px; background:#fff; border:1px solid var(--line);
  border-radius:12px; margin:0 0 16px;
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

.type-panel{display:none;}
.type-panel.is-active{display:block;}
.type-panel.is-hidden{display:none !important;}

/* plan cards */
.plan-grid{
  display:grid; gap:14px;
  grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
}
.plan-card{
  position:relative;
  background:#fff; border:1px solid var(--line); border-radius:16px;
  padding:22px 18px 18px;
  display:flex; flex-direction:column; align-items:center; gap:8px;
  text-align:center;
  box-shadow:var(--shadow-sm);
  text-decoration:none; color:inherit;
  transition:.25s; overflow:hidden;
  min-height:200px; justify-content:center;
  font:inherit; cursor:pointer; width:100%;
  -webkit-appearance:none; appearance:none;
}
.plan-card__body{
  display:flex; flex-direction:column; align-items:center; gap:8px; flex:1;
}
.plan-card::after{
  content:""; position:absolute; left:0; right:0; bottom:0; height:3px;
  background:linear-gradient(90deg,var(--gold-300),var(--gold-500)); transform:scaleX(0);
  transform-origin:left; transition:transform .3s ease;
}
.plan-card:hover{
  transform:translateY(-4px);
  box-shadow:var(--shadow-md); border-color:rgba(232,163,23,.4);
}
.plan-card:hover::after{transform:scaleX(1);}
.plan-card.is-hidden{display:none;}
.plan-card img{width:56px; height:56px; object-fit:contain;}
.plan-card b{font-size:22px; font-weight:800; color:var(--navy-900); letter-spacing:-.02em; margin-top:2px;}
.plan-card__name{
  font-size:13px; font-weight:600; color:var(--muted); line-height:1.35;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
  min-height:34px;
}
.plan-card small{
  display:inline-flex; align-items:center; gap:4px;
  font-size:11.5px; font-weight:700; color:var(--navy-700);
  background:#f7f9fd; padding:3px 9px; border-radius:999px;
}
.plan-card small svg{color:var(--gold-500);}

.cb-badge{
  position:absolute; top:10px; right:10px;
  background:linear-gradient(135deg,var(--gold-300),var(--gold-500));
  color:#2a1a00; font-size:11px; font-weight:800;
  padding:4px 10px; border-radius:999px; letter-spacing:.02em;
}

/* footer actions */
.op-block__foot{
  display:flex; align-items:center; gap:8px; flex-wrap:wrap;
  margin-top:14px; padding-top:12px;
  border-top:1px dashed var(--line);
}
.op-block__tip{
  margin:0; display:inline-flex; align-items:center; gap:7px;
  font-size:12.5px; font-weight:600; color:var(--muted);
}
.op-block__tip svg{color:var(--gold-500); flex:none;}

/* no match state */
.plan-no-match{
  display:flex; flex-direction:column; align-items:center; gap:8px;
  padding:22px 10px 8px; color:var(--muted); font-weight:600; font-size:13px;
  text-align:center;
}
.plan-no-match svg{color:var(--gold-400);}
.plan-no-match--panel{
  background:#f7f9fd; border:1px dashed var(--line); border-radius:14px;
  padding:40px 20px;
}
.plan-no-match--panel h4{margin:4px 0 0; font-size:16px; color:var(--navy-800);}
.plan-no-match--panel p{margin:0; font-size:13px;}

/* ======== quick recharge modal ======== */
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
.rc-modal__grid{
  display:grid; gap:12px; margin-bottom:14px;
}
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
.rc-modal__plan-body small svg{color:var(--gold-500);}
.rc-modal__plan-details{
  list-style:none; margin:12px 0 0; padding:0;
  border-top:1px dashed var(--line);
}
.rc-modal__plan-details li{
  display:flex; align-items:flex-start; gap:10px;
  padding:8px 4px; font-size:13px; font-weight:600; color:var(--navy-800);
  border-bottom:1px dashed rgba(11,42,91,.08);
}
.rc-modal__plan-details li:last-child{border-bottom:0;}
.rc-modal__plan-details li svg{
  flex:none; width:16px; height:16px; color:var(--gold-500);
  margin-top:1px;
}
.rc-modal__plan-details .rc-det-ic{
  flex:none; width:26px; height:26px; border-radius:8px;
  background:rgba(232,163,23,.12); color:var(--gold-600);
  display:inline-flex; align-items:center; justify-content:center;
}
.rc-modal__plan-details .rc-det-label{
  color:var(--muted); font-size:11.5px; font-weight:700;
  text-transform:uppercase; letter-spacing:.05em;
  flex:1; padding-top:2px;
}
.rc-modal__plan-details .rc-det-val{
  flex:none; max-width:55%; font-weight:700; color:var(--navy-900);
  text-align:right;
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
.rc-modal__hint p{
  margin:0; font-size:13px; font-weight:600; color:var(--navy-800); line-height:1.5;
}
.rc-modal__form input[readonly]{
  background:#f7f9fd; color:var(--navy-800); font-weight:700;
  cursor:default;
}
.rc-modal__confirm{text-align:center; padding:8px 4px 2px;}
.rc-modal__confirm h4{margin:0 0 8px; font-size:18px; font-weight:800; color:var(--navy-900);}
.rc-modal__confirm p{margin:0 0 16px; font-size:14px; font-weight:600; color:var(--navy-800); line-height:1.55;}
.rc-modal__confirm-actions{display:flex; gap:10px; justify-content:center; flex-wrap:wrap;}
.rc-modal.is-confirming .rc-modal__form,
.rc-modal.is-confirming .rc-modal__plan,
.rc-modal.is-confirming .rc-modal__hint{display:none;}
.rc-modal.is-success .rc-modal__confirm,
.rc-modal.is-generating .rc-modal__confirm{display:none;}

/* ======== responsive ======== */
@media (max-width:820px){
  .app-content{padding:18px;}
  .op-block{padding:16px;}
}

/* Tablet and below: single-column cards (one per row, horizontal layout) for readability */
@media (max-width:720px){
  .plan-grid{grid-template-columns:1fr; gap:12px;}
  .plan-card{
    flex-direction:row; align-items:flex-start; gap:14px;
    padding:16px; min-height:auto; text-align:left;
    justify-content:flex-start; border-radius:14px;
  }
  .plan-card__body{
    flex:1; min-width:0; display:flex; flex-direction:column; gap:5px;
    align-items:flex-start; text-align:left;
  }
  .plan-card img{width:48px; height:48px; flex:none;}
  .plan-card b{font-size:20px; margin:0;}
  .plan-card__name{
    font-size:13px; min-height:auto; text-align:left;
    color:var(--navy-800); font-weight:600;
  }
  .plan-card small{
    background:rgba(11,42,91,.06); color:var(--navy-700);
  }
  .cb-badge{
    position:static; align-self:flex-start;
    font-size:10px; padding:3px 9px; margin-top:2px;
  }
}

@media (max-width:580px){
  .plan-toolbar{gap:8px;}
  .plan-search{flex-basis:100%; min-width:0;}
  .plan-dd{flex:1 1 auto; min-width:0;}
  .plan-dd__btn{width:100%; height:40px; padding:0 12px; font-size:13px; justify-content:space-between;}
  .plan-dd__btn .plan-dd__label{max-width:none; flex:1; text-align:left;}
  #planReset{flex:0 0 auto; height:40px;}

  /* Stretch type-tabs to fill the bar — same 2-per-row clean tile style
     as the top category tabs on mobile, instead of cramped single-row. */
  .type-tabs{flex-wrap:wrap; gap:6px; padding:6px;}
  .type-tab{
    flex:1 1 calc(50% - 6px); min-width:0;
    padding:11px 8px; font-size:12.5px;
    overflow:hidden; text-overflow:ellipsis;
  }
  .type-tab em{display:none;}
  .cat-tabs em{display:none;}
  .kind-tabs{gap:6px; padding:6px;}
  .kind-tab{padding:10px 8px; font-size:13px;}

  .op-block__logo{width:38px; height:38px;}
  .op-block__title h4{font-size:15px;}
  .op-block__foot{flex-direction:column; align-items:stretch;}
  .op-block__foot .btn-admin{width:100%; justify-content:center;}
  .results-count{display:none;}

  .rc-modal{padding:12px;}
  .rc-modal__dialog{
    border-radius:18px;
    padding:18px 16px 16px;
    max-height:92vh;
    animation:rcPop .28s cubic-bezier(.2,.9,.3,1.2);
  }
  .rc-modal__logo{width:44px; height:44px;}
  .rc-modal__head h3{font-size:16px;}
  .rc-modal__submit{height:48px; font-size:14px;}
  .rc-modal__plan-details{font-size:12px; margin-top:10px;}
  .rc-modal__plan-details li{padding:7px 2px; gap:8px;}
  .rc-modal__plan-details .rc-det-ic{width:22px; height:22px;}
  .rc-modal__plan-details .rc-det-label{font-size:10.5px; min-width:0;}
  .rc-modal__plan-details .rc-det-val{max-width:50%; font-size:12px; word-break:break-word;}
}
</style>
@endpush

@push('scripts')
<script>
(function(){
  // ----- Category tabs -----
  var catTabs = document.querySelectorAll('#planCatTabs .cat-tab');
  var panels  = document.querySelectorAll('.plan-panel');

  function activateCat(tab){
    var slug = tab.dataset.catSlug;
    catTabs.forEach(function(t){
      var on = t === tab;
      t.classList.toggle('active', on);
      t.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    panels.forEach(function(p){
      if (p.dataset.catPanel === slug){
        p.classList.remove('is-leaving');
        p.classList.add('is-active');
      } else if (p.classList.contains('is-active')){
        p.classList.add('is-leaving');
        p.classList.remove('is-active');
        setTimeout(function(){ p.classList.remove('is-leaving'); }, 280);
      }
    });
    // re-apply filters when switching categories so the active panel reflects state
    applyFilters();
  }
  catTabs.forEach(function(tab){
    tab.addEventListener('click', function(){ activateCat(tab); });
  });

  var kindTabs = document.getElementById('mobileKindTabs');
  if (kindTabs){
    kindTabs.querySelectorAll('.kind-tab').forEach(function(btn){
      btn.addEventListener('click', function(){
        kindTabs.querySelectorAll('.kind-tab').forEach(function(t){
          var on = t === btn;
          t.classList.toggle('active', on);
          t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        applyFilters();
      });
    });
  }
  function currentKindFilter(){
    var active = document.querySelector('.plan-panel.is-active');
    var slug = active ? active.dataset.catPanel : '';
    if (kindTabs) kindTabs.hidden = slug !== 'mobile';
    if (slug !== 'mobile' || !kindTabs) return '';
    var on = kindTabs.querySelector('.kind-tab.active');
    return on ? (on.dataset.kind || '') : '';
  }

  // ----- Type sub-tabs (scoped per operator block) -----
  document.querySelectorAll('.op-block').forEach(function(block){
    var tabs = block.querySelectorAll('.type-tab');
    var pans = block.querySelectorAll('.type-panel');
    tabs.forEach(function(tab){
      tab.addEventListener('click', function(){
        var group = tab.dataset.group;
        var type  = tab.dataset.type;
        tabs.forEach(function(t){
          if (t.dataset.group === group) t.classList.toggle('active', t === tab);
        });
        pans.forEach(function(p){
          var key = group + '-' + type;
          if (p.dataset.typePanel === key) p.classList.add('is-active');
          else if (p.dataset.typePanel.indexOf(group + '-') === 0) p.classList.remove('is-active');
        });
      });
    });
  });

  // ----- Custom dropdowns -----
  document.querySelectorAll('[data-plan-dd]').forEach(function(dd){
    var btn = dd.querySelector('[data-plan-dd-btn]');
    var menu = dd.querySelector('[data-plan-dd-menu]');
    var label = dd.querySelector('[data-plan-dd-label]');
    var items = dd.querySelectorAll('.plan-dd__item');

    function positionMenu(){
      // Reset first so measurements are accurate
      menu.style.left = '';
      menu.style.right = '';
      menu.style.top = '';
      menu.style.position = '';

      if (window.matchMedia('(max-width: 580px)').matches) {
        // On mobile, pin to viewport edges to guarantee nothing overflows
        menu.style.position = 'fixed';
        var btnRect = btn.getBoundingClientRect();
        menu.style.left = '14px';
        menu.style.right = '14px';
        menu.style.width = 'auto';
        menu.style.minWidth = '0';
        menu.style.top = Math.min(btnRect.bottom + 6, window.innerHeight - 20) + 'px';
        // Cap height so it never goes off-screen
        menu.style.maxHeight = Math.max(180, window.innerHeight - btnRect.bottom - 40) + 'px';
      } else {
        // Desktop: align to right edge of button, but flip to left if overflow
        menu.style.position = 'absolute';
        menu.style.top = 'calc(100% + 6px)';
        var rect = dd.getBoundingClientRect();
        var menuWidth = 260;
        var spaceRight = window.innerWidth - rect.right;
        var spaceLeft = rect.left;
        if (spaceRight < menuWidth && spaceLeft >= menuWidth){
          menu.style.right = 'auto';
          menu.style.left = '0';
        } else {
          menu.style.left = 'auto';
          menu.style.right = '0';
        }
        menu.style.maxHeight = '360px';
      }
    }

    btn.addEventListener('click', function(e){
      e.stopPropagation();
      var isOpen = dd.classList.contains('is-open');
      closeAllDDs();
      if (!isOpen){
        dd.classList.add('is-open');
        menu.hidden = false;
        positionMenu();
      }
    });
    items.forEach(function(it){
      it.addEventListener('click', function(e){
        e.stopPropagation();
        items.forEach(function(i){ i.classList.remove('is-active'); });
        it.classList.add('is-active');
        label.textContent = it.textContent.trim();
        closeAllDDs();
        applyFilters();
      });
    });

    window.addEventListener('resize', function(){
      if (dd.classList.contains('is-open')) positionMenu();
    });
  });
  function closeAllDDs(){
    document.querySelectorAll('[data-plan-dd]').forEach(function(dd){
      dd.classList.remove('is-open');
      var m = dd.querySelector('[data-plan-dd-menu]'); if(m){
        m.hidden = true;
        m.style.left = ''; m.style.right = ''; m.style.top = '';
        m.style.position = ''; m.style.width = ''; m.style.minWidth = '';
        m.style.maxHeight = '';
      }
    });
  }
  document.addEventListener('click', closeAllDDs);

  // ----- Search -----
  var searchInput = document.getElementById('planSearch');
  var clearBtn = document.getElementById('planSearchClear');
  var resetBtn = document.getElementById('planReset');
  var resultsCount = document.getElementById('resultsCount');

  searchInput.addEventListener('input', applyFilters);
  clearBtn.addEventListener('click', function(){
    searchInput.value = '';
    applyFilters();
    searchInput.focus();
  });
  resetBtn.addEventListener('click', function(){
    searchInput.value = '';
    document.querySelectorAll('[data-plan-dd]').forEach(function(dd){
      var items = dd.querySelectorAll('.plan-dd__item');
      var label = dd.querySelector('[data-plan-dd-label]');
      items.forEach(function(i){ i.classList.toggle('is-active', !i.dataset.value); });
      var first = dd.querySelector('.plan-dd__item');
      if (first) label.textContent = first.textContent.trim();
    });
    if (kindTabs){
      kindTabs.querySelectorAll('.kind-tab').forEach(function(t){
        var on = !t.dataset.kind;
        t.classList.toggle('active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
      });
    }
    applyFilters();
  });

  function currentFilter(ddIndex){
    var dds = document.querySelectorAll('[data-plan-dd]');
    var dd = dds[ddIndex]; if(!dd) return '';
    var active = dd.querySelector('.plan-dd__item.is-active');
    return active ? (active.dataset.value || '') : '';
  }

  function applyFilters(){
    var q = (searchInput.value || '').trim().toLowerCase();
    var opFilter = currentFilter(0);   // operator group key
    var typeFilter = currentFilter(1); // plan type
    var kindFilter = currentKindFilter();
    var filtering = !!(q || opFilter || typeFilter || kindFilter);

    clearBtn.hidden = !q;

    // Reset ALL visibility state at the start so we never inherit stale DOM state
    document.querySelectorAll('[data-plan-card]').forEach(function(c){ c.classList.remove('is-hidden'); });
    document.querySelectorAll('.type-tab').forEach(function(t){ t.classList.remove('is-hidden', 'active'); });
    document.querySelectorAll('.type-panel').forEach(function(p){ p.classList.remove('is-hidden', 'is-active'); });
    document.querySelectorAll('.op-block').forEach(function(b){ b.classList.remove('is-hidden'); });
    document.querySelectorAll('[data-no-match]').forEach(function(n){ n.hidden = true; });
    document.querySelectorAll('[data-panel-empty]').forEach(function(n){ n.hidden = true; });
    document.querySelectorAll('[data-kind-part]').forEach(function(el){ el.hidden = false; });
    document.querySelectorAll('.op-block__foot').forEach(function(el){ el.hidden = false; });

    var activePanel = document.querySelector('.plan-panel.is-active');
    var totalVisible = 0;
    var visibleBlocksInActivePanel = 0;

    document.querySelectorAll('.op-block').forEach(function(block){
      var blockKey = block.dataset.opKey;
      var opMatch = !opFilter || opFilter === blockKey;
      var blockHasVisiblePlans = false;
      var visibleTypes = {};

      // Check bill-only detection using the raw text content (before any whitespace stripping)
      var opCountEl = block.querySelector('[data-op-count]');
      var opCountText = opCountEl ? (opCountEl.textContent || '').trim().toLowerCase() : '';
      var billOnly = opCountText.indexOf('bill payment service') !== -1;

      // Walk each plan card in this block
      block.querySelectorAll('[data-plan-card]').forEach(function(card){
        var type = card.dataset.type;
        var hay  = card.dataset.search || '';
        var typeMatch = !typeFilter || typeFilter === type;
        var textMatch = !q || hay.indexOf(q) !== -1;
        var show = opMatch && typeMatch && textMatch;
        card.classList.toggle('is-hidden', !show);
        if (show){
          blockHasVisiblePlans = true;
          visibleTypes[type] = (visibleTypes[type] || 0) + 1;
          totalVisible++;
        }
      });

      // Type tabs: activate the first tab by default; hide tabs with zero matches when filtering.
      // NOTE: bill-only blocks have NO type-tabs or type-panels at all — skip them cleanly.
      var typeTabs = block.querySelectorAll('.type-tab');
      var allTypePanels = block.querySelectorAll('.type-panel');

      if (typeTabs.length === 0) {
        // Bill-only block (no plans, no type tabs) — panels don't exist; nothing to toggle.
      } else {
        typeTabs.forEach(function(t){
          var tType = t.dataset.type;
          var count = visibleTypes[tType] || 0;
          var hide = false;
          if (typeFilter && typeFilter !== tType) hide = true;
          else if (filtering && count === 0) hide = true;
          t.classList.toggle('is-hidden', hide);
        });

        allTypePanels.forEach(function(panel){
          var panelType = panel.dataset.type;
          var cards = panel.querySelectorAll('[data-plan-card]');
          var anyVisible = false;
          cards.forEach(function(c){ if(!c.classList.contains('is-hidden')) anyVisible = true; });
          if (typeFilter && typeFilter !== panelType){
            panel.classList.remove('is-active');
            panel.classList.add('is-hidden');
          } else {
            panel.classList.toggle('is-hidden', filtering && !anyVisible);
          }
        });
      }

      // Active tab logic
      if (opMatch && typeTabs.length > 0){
        if (typeFilter){
          // Explicit type filter wins
          typeTabs.forEach(function(t){
            t.classList.toggle('active', t.dataset.type === typeFilter);
          });
          allTypePanels.forEach(function(p){
            if (p.dataset.type === typeFilter) p.classList.add('is-active');
            else p.classList.remove('is-active');
          });
        } else {
          // Activate the first non-hidden tab
          var firstVisibleTab = block.querySelector('.type-tab:not(.is-hidden)');
          if (firstVisibleTab){
            typeTabs.forEach(function(t){ t.classList.toggle('active', t === firstVisibleTab); });
            allTypePanels.forEach(function(p){
              p.classList.toggle('is-active', p.dataset.type === firstVisibleTab.dataset.type);
            });
          } else {
            // No visible tabs (all filtered out) — deactivate everything
            typeTabs.forEach(function(t){ t.classList.remove('active'); });
            allTypePanels.forEach(function(p){ p.classList.remove('is-active'); });
          }
        }
      }

      // Prepaid / Postpaid split — only when the Mobile tab is open
      if (kindFilter){
        block.querySelectorAll('[data-kind-part]').forEach(function(el){
          el.hidden = el.dataset.kindPart !== kindFilter;
        });
      }
      var foot = block.querySelector('.op-block__foot');
      if (foot){
        var anyFoot = foot.querySelector('.btn-admin:not([hidden]), .op-block__tip:not([hidden])');
        foot.hidden = !anyFoot;
      }

      // Decide block visibility
      var hasBillCta = !!block.querySelector('.op-block__foot .btn-admin--primary:not([hidden])');
      var hasBillTip = !!block.querySelector('.op-block__tip:not([hidden])');
      var kindOk = true;
      if (kindFilter === 'prepaid') kindOk = block.dataset.linePrepaid === '1';
      if (kindFilter === 'postpaid') kindOk = block.dataset.linePostpaid === '1';

      var blockVisible = opMatch && kindOk && (blockHasVisiblePlans || hasBillCta || hasBillTip || (billOnly && kindFilter !== 'prepaid'));
      block.classList.toggle('is-hidden', !blockVisible);

      // Per-block no-match message: only show when there's an active filter AND
      // the block is visible (op matches) but has zero plan cards to show AND
      // this isn't a bill-only / bill-tip / bill-cta block.
      var noMatch = block.querySelector('[data-no-match]');
      if (noMatch){
        var showNoMatch = blockVisible && filtering && !blockHasVisiblePlans && !billOnly && !hasBillCta && !hasBillTip;
        noMatch.hidden = !showNoMatch;
      }

      if (blockVisible && activePanel && activePanel.contains(block)){
        visibleBlocksInActivePanel++;
      }
    });

    // Panel empty state
    if (activePanel){
      var panelEmpty = activePanel.querySelector('[data-panel-empty]');
      if (panelEmpty){
        panelEmpty.hidden = !filtering || visibleBlocksInActivePanel > 0;
      }
    }

    // Results count
    if (resultsCount){
      if (filtering){
        resultsCount.textContent = totalVisible + ' plan' + (totalVisible === 1 ? '' : 's') + ' found';
      } else {
        var all = document.querySelectorAll('[data-plan-card]').length;
        resultsCount.textContent = all + ' plans total';
      }
    }
  }

  // Initial count render
  applyFilters();

  // ---------- Quick Recharge Modal ----------
  var modal    = document.getElementById('rcModal');
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
  var currentMode = 'plan';
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

  // icon name -> inline SVG (small, so we don't depend on blade in JS)
  var iconSvg = {
    wifi: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>',
    phone: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    grid: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    users: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    bolt: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    clock: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'tv-card': '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>',
    bill: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
  };

  function lockBodyScroll(){
    // Block wheel / touchmove / keyboard scroll on the page while modal is open.
    // Use capture so we see events before the scroll handler fires;
    // allow scrolling only within the inner .rc-modal__dialog.
    // We deliberately do NOT set overflow:hidden on body/html — that resets
    // window.scrollY and breaks position:sticky (sidebar jumps on open).
    window.addEventListener('wheel', onScrollAttempt, {capture:true, passive:false});
    window.addEventListener('touchmove', onScrollAttempt, {capture:true, passive:false});
    window.addEventListener('keydown', onKeyScrollAttempt, {capture:true});
  }
  function unlockBodyScroll(){
    window.removeEventListener('wheel', onScrollAttempt, {capture:true, passive:false});
    window.removeEventListener('touchmove', onScrollAttempt, {capture:true, passive:false});
    window.removeEventListener('keydown', onKeyScrollAttempt, {capture:true});
  }
  var dialogEl = null;
  function getDialog(){
    if (!dialogEl) dialogEl = modal.querySelector('.rc-modal__dialog');
    return dialogEl;
  }
  function isScrollableSurface(target){
    // Allow scroll inside the dialog content, OR on the modal container itself
    // (which has overflow-y:auto so it can scroll when dialog is taller than viewport).
    var d = getDialog();
    if (!d) return false;
    if (target === modal || target === d || d.contains(target)) return true;
    return false;
  }
  function onScrollAttempt(e){
    // If the event happened inside the dialog / modal scroll surface, allow scroll there.
    // Anything else (backdrop, outside the modal) must NOT scroll the page.
    if (isScrollableSurface(e.target)) return;
    e.preventDefault();
  }
  function onKeyScrollAttempt(e){
    var keys = [32,33,34,35,36,38,40]; // space, pgup/pgdn, home/end, up/down
    if (keys.indexOf(e.keyCode) === -1) return;
    if (isScrollableSurface(e.target)) return;
    // Don't block typing keys; only block scroll keys
    e.preventDefault();
  }

  // mode: 'plan' (card click, prefilled plan+amount), 'reload' (custom amount), 'bill' (bill payment)
  function openModal(card){
    var svcId  = card.dataset.serviceId;
    var amount = card.dataset.amount || '';
    var name   = card.dataset.name || '';
    var val    = card.dataset.validity || '';
    var logo   = card.dataset.logo;
    var op     = card.dataset.opName;
    var cb     = card.dataset.cb;
    var mode   = card.dataset.mode || 'plan';
    currentMode = mode;
    var hideNotify = card.dataset.hideNotify === '1' || /postpaid/i.test(card.dataset.opName || '');
    var details;
    try { details = JSON.parse(card.dataset.details || '[]'); } catch(e){ details = []; }

    mSvcId.value  = svcId;
    mAcc.value = '';
    mNotify.value = '';
    if (mNotify){ mNotify.disabled = hideNotify; mNotify.value = ''; }
    if (mNotifyField) mNotifyField.hidden = hideNotify;
    if (mConfirm) mConfirm.hidden = true;
    modal.classList.remove('is-confirming');
    mPlanLogo.src = logo; mLogo.src = logo;
    mOpName.textContent = op;

    if (mode === 'plan'){
      // Preselected plan card — show selected plan card, prefill amount
      mTitle.textContent = 'Recharge — ' + op;
      mAccLabel.innerHTML = 'Mobile / Account Number <span class="req">*</span>';
      mAmountLabel.innerHTML = 'Amount (LKR) <span class="req">*</span>';
      mAccountInput.placeholder = 'e.g. 0771234567';
      mAmountInput.placeholder = '';
      mAmountInput.readOnly = true;
      mAmountInput.min = '50';
      mSubmitLabel.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Recharge Now';
      mAmount.value = amount;
      mPlanName.textContent = name;
      mPlanPr.textContent = 'LKR ' + Number(amount).toLocaleString('en-LK', {minimumFractionDigits:0, maximumFractionDigits:2});
      if (val){ mPlanVal.textContent = val; mPlanVal.style.display=''; } else { mPlanVal.textContent=''; mPlanVal.style.display='none'; }
      if (cb && parseFloat(cb) > 0){
        mPlanCb.style.display = '';
        mPlanCb.textContent = '+LKR ' + cb;
      } else {
        mPlanCb.style.display = 'none';
      }
      // render details
      mPlanDetails.innerHTML = '';
      if (details && details.length){
        details.forEach(function(d){
          var li = document.createElement('li');
          var ic = iconSvg[d.icon] || iconSvg.bolt;
          li.innerHTML = '<span class="rc-det-ic">' + ic + '</span>'
            + '<span class="rc-det-label"></span>'
            + '<span class="rc-det-val"></span>';
          li.querySelector('.rc-det-label').textContent = d.label || '';
          li.querySelector('.rc-det-val').textContent = d.value || '';
          mPlanDetails.appendChild(li);
        });
        mPlanDetails.style.display = '';
      } else {
        mPlanDetails.style.display = 'none';
      }
      mPlanBox.style.display = '';
      mHint.hidden = true;
    } else if (mode === 'reload'){
      // Custom reload/topup — empty amount, user types
      mTitle.textContent = 'Custom Reload — ' + op;
      mAccLabel.innerHTML = 'Mobile Number <span class="req">*</span>';
      mAmountLabel.innerHTML = 'Reload Amount (LKR) <span class="req">*</span>';
      mAccountInput.placeholder = 'e.g. 0771234567';
      mAmountInput.placeholder = 'Enter amount (e.g. 250)';
      mAmountInput.readOnly = false;
      mAmountInput.min = '50';
      mAmountInput.value = '';
      mSubmitLabel.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Reload Now';
      mPlanBox.style.display = 'none';
      mHintIc.innerHTML = iconSvg.bolt;
      mHintText.textContent = 'Minimum reload is LKR 50. Enter an amount between LKR 50 and LKR 100,000 — the exact amount will be credited to the mobile number.';
      mHint.hidden = false;
    } else {
      // Bill payment — account number, exact bill amount
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
      mHintText.textContent = 'Minimum bill payment is LKR 10. Enter the exact bill amount due and your account/reference number. Payment will be processed immediately.';
      mHint.hidden = false;
    }

    // Reset success / generating state / form errors
    modal.classList.remove('is-success', 'is-generating');
    mSuccess.hidden = true;
    mGenerating.hidden = true;
    mForm.style.display = '';
    mSubmit.disabled = false;
    mSubmit.classList.remove('is-loading');
    mSpinner.hidden = true;
    mLabel.hidden = false;
    // Reset success icon/title to defaults (green check)
    if (mSuccessIcon){
      mSuccessIcon.className = 'rc-modal__success-icon';
      mSuccessIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
    }
    if (mSuccessTitle) mSuccessTitle.textContent = 'Recharge Successful!';
    if (mViewOrder) mViewOrder.textContent = 'View Order';
    // Reset validation styling (if any)
    mAmountInput.classList.remove('is-invalid');
    mAccountInput.classList.remove('is-invalid');

    // Lock page scroll behind the modal (event-based — no CSS overflow change,
    // which would reset scrollY and break the sticky sidebar).
    lockBodyScroll();

    modal.hidden = false;
    modal.setAttribute('aria-hidden','false');
    setTimeout(function(){
      if (mode === 'plan') mAcc.focus();
      else mAcc.focus();
    }, 80);
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
    // Reset readOnly for next open
    mAmountInput.readOnly = false;
  }

  document.querySelectorAll('[data-rc-close]').forEach(function(el){
    el.addEventListener('click', closeModal);
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });

  // Click a plan card to open modal (prefilled plan)
  document.addEventListener('click', function(e){
    var card = e.target.closest('[data-plan-card]');
    if (card){
      if (modal.contains(card)) return;
      e.preventDefault();
      openModal(card);
      return;
    }
    // Click Custom amount / Bill Pay button to open modal (empty/custom mode)
    var btn = e.target.closest('[data-rc-custom]');
    if (btn){
      if (modal.contains(btn)) return;
      e.preventDefault();
      openModal(btn);
    }
  });

  // Submit recharge via AJAX
  function hideConfirm(){
    if (mConfirm) mConfirm.hidden = true;
    modal.classList.remove('is-confirming');
    mForm.style.display = '';
    if (currentMode === 'plan' && mPlanBox) mPlanBox.style.display = '';
    else if (currentMode !== 'plan' && mHint) mHint.hidden = false;
  }
  function showOrderConfirm(){
    var amt = parseFloat(mAmount.value || '0');
    var acc = (mAcc.value || '').trim();
    var op = (mOpName.textContent || '').trim();
    var isBill = currentMode === 'bill';
    var title = document.getElementById('rcConfirmTitle') || document.querySelector('#rcConfirm h4');
    if (title) title.textContent = isBill ? 'Confirm this payment?' : 'Confirm this reload?';
    if (mConfirmText){
      mConfirmText.textContent = (isBill ? 'Are you sure you want to pay' : 'Are you sure you want to reload')
        + ' LKR ' + amt.toFixed(2) + ' to ' + acc + (op ? (' for ' + op) : '') + ' from your wallet?';
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
    var MIN_SPIN = 2200; // match landing.js MIN_BTN_SPIN_MS

    // Use the global setBtnLoading helper if present (handles .btn-label/.btn-spinner wiring)
    if (typeof window.setBtnLoading === 'function'){
      window.setBtnLoading(mSubmit, true);
    } else {
      mSubmit.disabled = true;
      mSubmit.classList.add('is-loading');
      mSpinner.hidden = false;
      mLabel.hidden = false;
    }

    function stopBtnSpinner(cb){
      var elapsed = performance.now() - started;
      var wait = Math.max(0, MIN_SPIN - elapsed);
      setTimeout(function(){
        if (typeof window.setBtnLoading === 'function'){
          window.setBtnLoading(mSubmit, false);
        } else {
          mSubmit.disabled = false;
          mSubmit.classList.remove('is-loading');
          mSpinner.hidden = true;
          mLabel.hidden = false;
        }
        if (cb) cb();
      }, wait);
    }

    fetch('{{ route('recharge.confirm') }}', {
      method: 'POST',
      body: fd,
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept':'application/json'},
      credentials: 'same-origin'
    })
    .then(function(r){ return r.json().then(function(d){ return {ok:r.ok, status:r.status, data:d}; }); })
    .then(function(res){
      if (!res.ok || !res.data.ok){
        throw new Error((res.data && res.data.message) || 'Order failed. Please try again.');
      }
      var o = res.data.order;
      var isSuccess = res.data.status === 'success';
      var hasInvoice = !!res.data.has_invoice;

      // Spin for at least MIN_SPIN ms, then transition to generating/success state
      stopBtnSpinner(function(){
      if (isSuccess && hasInvoice){
        // Show the "generating invoice" spinner briefly before revealing final buttons
        mForm.style.display = 'none';
        mPlanBox.style.display = 'none';
        mHint.hidden = true;
        mSuccess.hidden = true;
        mGenerating.hidden = false;
        modal.classList.remove('is-success');
        modal.classList.add('is-generating');
        if (window.toast) window.toast(res.data.message || 'Recharge successful!', 'success');

        // Invoice was generated server-side; pause ~1200ms as a "generating" UX beat
        setTimeout(function(){
          mSuccessIcon.className = 'rc-modal__success-icon rc-modal__success-icon--success';
          mSuccessIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
          mSuccessTitle.textContent = 'Recharge Successful!';
          mSuccessMsg.textContent = res.data.message || ('Payment of LKR ' + Number(o.amount).toFixed(2) + ' to ' + o.account + ' completed.');
          mViewOrder.href = res.data.invoice_url || o.redirect;
          mViewOrder.textContent = 'View Receipt';
          mViewOrder.target = '_blank';
          if (res.data.download_url){
            mDownload.href = res.data.download_url;
            mDownload.hidden = false;
          } else {
            mDownload.hidden = true;
          }
          mGenerating.hidden = true;
          mSuccess.hidden = false;
          modal.classList.remove('is-generating');
          modal.classList.add('is-success');
        }, 1200);
      } else if (isSuccess){
        // Success but no invoice yet (edge case — shouldn't normally happen)
        mSuccessIcon.className = 'rc-modal__success-icon rc-modal__success-icon--success';
        mSuccessIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
        mSuccessTitle.textContent = 'Recharge Successful!';
        mSuccessMsg.textContent = res.data.message || ('Payment of LKR ' + Number(o.amount).toFixed(2) + ' to ' + o.account + ' completed.');
        mViewOrder.href = o.redirect;
        mViewOrder.textContent = 'View Order';
        mViewOrder.target = '_blank';
        mDownload.hidden = true;
        mForm.style.display = 'none';
        mPlanBox.style.display = 'none';
        mHint.hidden = true;
        mSuccess.hidden = false;
        modal.classList.add('is-success');
        if (window.toast) window.toast(res.data.message || 'Recharge successful!', 'success');
      } else {
        // Pending / processing — show an hourglass/info state
        mSuccessIcon.className = 'rc-modal__success-icon rc-modal__success-icon--pending';
        mSuccessIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
        mSuccessTitle.textContent = 'Recharge is Processing';
        mSuccessMsg.textContent = res.data.message || ('Your recharge of LKR ' + Number(o.amount).toFixed(2) + ' to ' + o.account + ' has been sent and is being processed.');
        mViewOrder.href = o.redirect;
        mViewOrder.textContent = 'Track Order Status';
        mViewOrder.target = '_blank';
        mDownload.hidden = true;
        mForm.style.display = 'none';
        mPlanBox.style.display = 'none';
        mHint.hidden = true;
        mSuccess.hidden = false;
        modal.classList.add('is-success');
        if (window.toast) window.toast(res.data.message || 'Recharge is processing…', 'info');
      }
      }); // end stopBtnSpinner callback
    })
    .catch(function(err){
      // Respect minimum spinner time before re-enabling
      stopBtnSpinner(function(){
        if (window.toast) window.toast(err.message || 'Something went wrong.', 'error');
        else alert(err.message || 'Something went wrong.');
      });
    });
  }
})();
</script>


@endpush
