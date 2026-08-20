/* ================================================================
   landing.js — shared site behaviors
   ----------------------------------------------------------------
   Loader rule (per request):
     The full-page gold 8-dot spinner shows ONLY on these
     three first-impression moments:
       1. Loading the homepage (/)
       2. Entering the customer dashboard (/dashboard)
       3. Entering the admin panel (/admin)
     NO loader of any kind on any other navigation, link click,
     or form submit. Forms use the per-button inline spinner
     (.is-loading + .btn-spinner) so they still feel responsive.
   ================================================================ */

/* ---------- footer year ---------- */
document.addEventListener('DOMContentLoaded', function () {
  var y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();
});

/* ---------- live Sri Lanka clock ---------- */
(function(){
  var el = document.getElementById('clock');
  if(!el) return;
  var dOpt = {timeZone:'Asia/Colombo', weekday:'short', day:'2-digit', month:'short'};
  var tOpt = {timeZone:'Asia/Colombo', hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true};
  var dFmt = new Intl.DateTimeFormat('en-GB', dOpt);
  var tFmt = new Intl.DateTimeFormat('en-GB', tOpt);
  function tick(){
    var now = new Date();
    el.textContent = dFmt.format(now) + ' · ' + tFmt.format(now).toUpperCase();
    el.setAttribute('datetime', now.toISOString());
  }
  tick();
  setTimeout(function(){ tick(); setInterval(tick, 1000); }, 1000 - (Date.now() % 1000));
})();

/* ---------- sticky nav shadow ---------- */
(function(){
  var nav = document.getElementById('nav');
  if (!nav) return;
  function onScroll(){
    nav.classList.toggle('is-stuck', (window.scrollY || document.documentElement.scrollTop) > 12);
  }
  addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();

/* ---------- mobile drawer ---------- */
(function(){
  var burger = document.getElementById('burger'),
      drawer = document.getElementById('drawer'),
      scrim  = document.getElementById('scrim'),
      close  = document.getElementById('close');
  if (!burger || !drawer || !scrim) return;
  function toggle(open){
    drawer.classList.toggle('open', open);
    scrim.classList.toggle('open', open);
    burger.classList.toggle('open', open);
  }
  burger.onclick = function(e){ e.stopPropagation(); toggle(!drawer.classList.contains('open')); };
  if (close) close.onclick = function(){ toggle(false); };
  scrim.onclick  = function(){ toggle(false); };
  drawer.querySelectorAll('a, button').forEach(function(el){
    el.addEventListener('click', function(){ toggle(false); });
  });
})();

/* ---------- account dropdown ---------- */
(function(){
  var menu = document.getElementById('accountMenu');
  var btn  = document.getElementById('accountBtn');
  if (!menu || !btn) return;
  function open(open_){
    menu.classList.toggle('is-open', open_);
    btn.setAttribute('aria-expanded', open_ ? 'true' : 'false');
  }
  btn.addEventListener('click', function(e){
    e.stopPropagation();
    open(!menu.classList.contains('is-open'));
  });
  document.addEventListener('click', function(e){ if (!menu.contains(e.target)) open(false); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') open(false); });
})();

/* ================================================================
   PAGE LOADER
   ----------------------------------------------------------------
   Shows the 3-second gold 8-dot spinner ONLY on:
     1. Loading / arriving at the homepage (/) — always
     2. First entry into the customer dashboard (/dashboard or
        any /dashboard/* page when coming from outside)
     3. First entry into the admin panel (/admin/* when coming
        from outside)
   Clicking between pages inside dashboard or inside admin shows
   NO loader. Non-entry pages (login, register, order details,
   service forms, etc.) show NO loader either — just the page,
   instant. Per-button spinners handle form feedback.
   ================================================================ */
(function(){
  var WELCOME_MS  = 3000;
  var FAILSAFE_MS = 8000;

  var loader  = document.getElementById('pageLoader');
  var htmlEl  = document.documentElement;
  if (!loader) return;

  var path = location.pathname.replace(/\/+$/,'') || '/';

  function currentShell(){
    if (path === '/' || path === '') return 'home';
    if (path.indexOf('/admin') === 0) return 'admin';
    if (path.indexOf('/dashboard') === 0) return 'dashboard';
    return 'other';
  }
  var shell = currentShell();
  var lastShell = sessionStorage.getItem('hpr_last_shell');

  var showWelcome = false;
  if (shell === 'home'){
    showWelcome = true;
  } else if (shell === 'dashboard' && lastShell !== 'dashboard'){
    showWelcome = true;
  } else if (shell === 'admin' && lastShell !== 'admin'){
    showWelcome = true;
  }

  sessionStorage.setItem('hpr_last_shell', shell);

  function hideLoader(){
    loader.classList.remove('is-active');
    loader.classList.remove('no-transition');
    htmlEl.classList.remove('is-loading');
  }

  addEventListener('pageshow', function(e){
    if (e.persisted) hideLoader();
  });

  requestAnimationFrame(function(){
    requestAnimationFrame(function(){ loader.classList.remove('no-transition'); });
  });

  if (!showWelcome){
    requestAnimationFrame(hideLoader);
    return;
  }

  var failsafe = setTimeout(function(){ hideLoader(); }, FAILSAFE_MS);

  function finish(){
    clearTimeout(failsafe);
    var elapsed = performance.now();
    var wait = Math.max(0, WELCOME_MS - elapsed);
    setTimeout(hideLoader, wait);
  }

  if (document.readyState === 'complete') finish();
  else addEventListener('load', finish, { once: true });
})();

/* ================================================================
   PAGE ENTRANCE ANIMATION
   ----------------------------------------------------------------
   Adds .page-reveal to <body> immediately so all the CSS above is
   armed (content starts hidden/offset), then flips .is-revealed
   to start the cascade. Waits for the welcome loader to disappear
   first so you don't see content animate behind the loader.
   ================================================================ */
(function(){
  var body = document.body;
  if (!body) return;
  body.classList.add('page-reveal');

  // REVEAL_DURATION must be >= longest CSS transition (.55s) + max stagger (~400ms)
  // so the cleanup only fires AFTER every child has settled at opacity:1 / translateY(0).
  var REVEAL_DURATION = 1100;

  function cleanupReveal(){
    // After the entrance animation finishes, flip a final-state class on <body>
    // that the CSS uses to set transform:none on every animated element.
    // Leaving transform:translateY(0) (even identity) creates a containing block
    // for position:fixed descendants, which breaks modals/dropdowns inside cards.
    body.classList.add('is-revealed-done');
  }

  function reveal(){
    requestAnimationFrame(function(){
      requestAnimationFrame(function(){
        body.classList.add('is-revealed');
        setTimeout(cleanupReveal, REVEAL_DURATION);
      });
    });
  }

  var loader = document.getElementById('pageLoader');
  var htmlEl = document.documentElement;
  function isLoaderActive(){
    return !!(loader && (loader.classList.contains('is-active') || htmlEl.classList.contains('is-loading')));
  }
  if (isLoaderActive()){
    var tries = 0;
    var iv = setInterval(function(){
      tries++;
      if (!isLoaderActive() || tries > 250){
        clearInterval(iv);
        setTimeout(reveal, 90);
      }
    }, 50);
  } else {
    reveal();
  }
})();

/* ================================================================
   BUTTON SPINNER HELPERS
   ----------------------------------------------------------------
   - ensureBtnSpinner(btn): wraps the button's existing content in
     a .btn-label span (if not already) and guarantees a .btn-spinner
     span exists, hidden. Also ensures the button is a positioning
     context so the spinner centers correctly.
   - setBtnLoading(btn, loading): adds/removes .is-loading and
     hides/shows the spinner.
   - MIN_BTN_SPIN_MS (2200): minimum time the spinner stays visible
     during AJAX submits so fast responses don't flicker.
   ================================================================ */
var MIN_BTN_SPIN_MS = 2200;

function ensureBtnSpinner(btn){
  if (!btn) return null;
  // Make sure button is a positioning container for the absolute spinner
  var cs = getComputedStyle(btn);
  if (cs.position === 'static'){
    btn.style.position = 'relative';
  }
  var sp = btn.querySelector('.btn-spinner');
  if (!sp){
    sp = document.createElement('span');
    sp.className = 'btn-spinner';
    sp.hidden = true;
    btn.appendChild(sp);
  }
  // If there's no .btn-label wrapper yet, wrap all non-spinner children
  if (!btn.querySelector('.btn-label')){
    var label = document.createElement('span');
    label.className = 'btn-label';
    while (btn.firstChild && btn.firstChild !== sp){
      label.appendChild(btn.firstChild);
    }
    btn.insertBefore(label, sp);
  }
  return sp;
}

function setBtnLoading(btn, loading){
  if (!btn) return;
  var sp = ensureBtnSpinner(btn);
  btn.classList.toggle('is-loading', !!loading);
  btn.disabled = !!loading;
  if (sp) sp.hidden = !loading;
}

/* ================================================================
   NORMAL (non-AJAX) FORM SUBMITS: inline per-button spinner
   ----------------------------------------------------------------
   - Skip forms with [data-ajax] (handled below)
   - Skip forms with [data-no-auto-spin] (page-specific JS manages it)
   - Wrap button content the first time, then toggle .is-loading
   - The browser navigates/reloads so we never remove the state here
   ================================================================ */
document.addEventListener('submit', function(e){
  var form = e.target;
  if (!form || form.tagName !== 'FORM') return;
  if (form.matches('[data-ajax]')) return;
  if (form.matches('[data-no-auto-spin]')) return;
  var btn = form.querySelector('button[type=submit]');
  if (btn) setBtnLoading(btn, true);
});

/* ================================================================
   TOAST helper
   ================================================================ */
(function(){
  window.toast = function(msg, type){
    type = type || 'info';
    var host = document.getElementById('toastHost');
    if (!host){
      host = document.createElement('div');
      host.id = 'toastHost';
      document.body.appendChild(host);
    }
    var el = document.createElement('div');
    el.className = 'toast toast--' + type;
    el.textContent = msg;
    host.appendChild(el);
    requestAnimationFrame(function(){
      requestAnimationFrame(function(){ el.classList.add('show'); });
    });
    setTimeout(function(){
      el.classList.remove('show');
      setTimeout(function(){ el.remove(); }, 350);
    }, 3200);
  };
})();

/* ================================================================
   AJAX quick actions (admin toggle/import/save, settings, etc.)
   ----------------------------------------------------------------
   - Forms marked [data-ajax] submit via fetch and get a toast
   - Spinner stays visible for at least MIN_BTN_SPIN_MS (2.2s)
     so users always see the "Processing…" feedback
   - If form has [data-ajax-refresh] we reload after success
   ================================================================ */
document.addEventListener('submit', function(e){
  var form = e.target;
  if (!form || !form.matches('[data-ajax]')) return;
  e.preventDefault();
  if (form.dataset.busy === '1') return;
  form.dataset.busy = '1';

  var btn = form.querySelector('button[type=submit]');
  var started = performance.now();
  setBtnLoading(btn, true);

  var action = form.getAttribute('action') || location.href;
  var method = (form.getAttribute('method') || 'post').toUpperCase();
  var body = new FormData(form);

  var opts = { method: method, credentials: 'same-origin', headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  }};
  if (method !== 'GET') opts.body = body;

  function done(ok, msg, redirectUrl){
    var elapsed = performance.now() - started;
    var wait = Math.max(0, MIN_BTN_SPIN_MS - elapsed);
    setTimeout(function(){
      form.dataset.busy = '0';
      setBtnLoading(btn, false);
      if (msg) window.toast(msg, ok ? 'success' : 'error');
      if (ok && redirectUrl){
        setTimeout(function(){ location.href = redirectUrl; }, 600);
      } else if (ok && form.matches('[data-ajax-refresh]')){
        setTimeout(function(){ location.reload(); }, 500);
      }
    }, wait);
  }

  fetch(action, opts).then(function(res){
    var ct = res.headers.get('content-type') || '';
    if (ct.indexOf('application/json') !== -1){
      return res.json().then(function(data){
        var ok = res.ok && data.ok !== false;
        var msg = data.message || (ok ? 'Done.' : 'Something went wrong.');
        if (!ok && data.errors){
          msg = Object.values(data.errors).flat().join(' · ');
        }
        // Support server-side redirect (e.g. after admin failover to new order).
        // If the form has [data-ajax-redirect] (boolean flag), navigate to data.redirect from JSON.
        // If [data-ajax-redirect] has a URL value, use that as a fallback.
        var redirectFlag = form.hasAttribute('data-ajax-redirect');
        var redirectAttrVal = form.getAttribute('data-ajax-redirect');
        var redirectUrl = data.redirect || (ok && redirectFlag ? (redirectAttrVal || null) : null);
        done(ok, msg, redirectUrl);
        return { ok: ok, data: data };
      });
    }
    return res.text().then(function(){
      done(res.ok, res.ok ? (form.getAttribute('data-ajax-success') || 'Done.') : 'Something went wrong.');
    });
  }).catch(function(){
    done(false, 'Network error. Please try again.');
  });
});
