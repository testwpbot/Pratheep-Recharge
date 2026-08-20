{{--
  Page loader — 3-second gold 8-dot spinner shown ONLY on:
    1. Homepage (/) load
    2. First entry into customer dashboard (/dashboard*)
    3. First entry into admin panel (/admin*)
  Hidden instantly on every other page/navigation.
  Starts visible with no-transition so first paint is 100% opaque
  (no white flash) — JS decides whether to keep it up or tear it down.
--}}
<div class="page-loader is-active no-transition" id="pageLoader" role="status" aria-live="polite" aria-label="Loading page">
  <div class="dot-spinner">
    <div class="dot-spinner__dot"></div>
    <div class="dot-spinner__dot"></div>
    <div class="dot-spinner__dot"></div>
    <div class="dot-spinner__dot"></div>
    <div class="dot-spinner__dot"></div>
    <div class="dot-spinner__dot"></div>
    <div class="dot-spinner__dot"></div>
    <div class="dot-spinner__dot"></div>
    <span class="dot-spinner__label">Loading…</span>
  </div>
</div>

<div id="toastHost"></div>
