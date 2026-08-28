@if(!empty($dashboardAlerts) && $dashboardAlerts->isNotEmpty())
  <div class="hpr-alert-pop" id="hprAlertPop">
    <div class="hpr-alert-pop__backdrop" data-alert-pop-close></div>
    <div class="hpr-alert-pop__stage" role="dialog" aria-modal="true" aria-label="Notice">
      @foreach ($dashboardAlerts as $alert)
        <div class="hpr-alert-pop__slide" data-alert-slide @unless($loop->first) hidden @endunless>
          @include('partials.dashboard-alert', ['alert' => $alert])
        </div>
      @endforeach
    </div>
  </div>
  <script>
  (function(){
    var pop = document.getElementById('hprAlertPop');
    if (!pop || window.__hprAlertPopReady) return;
    window.__hprAlertPopReady = true;
    document.body.appendChild(pop);

    var slides = Array.prototype.slice.call(pop.querySelectorAll('[data-alert-slide]'));
    var i = 0;
    var scrollY = 0;
    var locked = false;

    function blockPageScroll(e){
      if (e.target && e.target.closest && e.target.closest('.hpr-alert-pop__stage')) return;
      e.preventDefault();
    }
    function lockScroll(){
      if (locked) return;
      locked = true;
      scrollY = window.scrollY || window.pageYOffset || 0;
      document.documentElement.classList.add('hpr-pop-lock');
      document.body.classList.add('hpr-pop-lock');
      document.body.style.top = '-' + scrollY + 'px';
      window.addEventListener('wheel', blockPageScroll, {passive:false, capture:true});
      window.addEventListener('touchmove', blockPageScroll, {passive:false, capture:true});
    }
    function unlockScroll(){
      if (!locked) return;
      locked = false;
      document.documentElement.classList.remove('hpr-pop-lock');
      document.body.classList.remove('hpr-pop-lock');
      document.body.style.top = '';
      window.removeEventListener('wheel', blockPageScroll, {capture:true});
      window.removeEventListener('touchmove', blockPageScroll, {capture:true});
      window.scrollTo(0, scrollY);
    }

    function show(n){
      slides.forEach(function(s, idx){ s.hidden = idx !== n; });
      i = n;
      pop.hidden = false;
      pop.setAttribute('aria-hidden', 'false');
      lockScroll();
    }
    function closeCurrent(persistForm){
      if (persistForm){
        fetch(persistForm.action, {
          method:'POST',
          headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
          credentials:'same-origin',
          body: new FormData(persistForm)
        }).catch(function(){});
      }
      var next = i + 1;
      if (next < slides.length) show(next);
      else {
        pop.hidden = true;
        pop.setAttribute('aria-hidden', 'true');
        unlockScroll();
      }
    }

    pop.addEventListener('submit', function(e){
      var form = e.target.closest('[data-alert-dismiss]');
      if (!form) return;
      e.preventDefault();
      closeCurrent(form);
    });
    pop.addEventListener('click', function(e){
      if (e.target.closest('[data-alert-pop-close]')){
        var form = slides[i] && slides[i].querySelector('[data-alert-dismiss]');
        closeCurrent(form || null);
      }
    });
    document.addEventListener('keydown', function(e){
      if (e.key === 'Escape' && !pop.hidden){
        var form = slides[i] && slides[i].querySelector('[data-alert-dismiss]');
        closeCurrent(form || null);
      }
    });

    show(0);
  })();
  </script>
@endif
