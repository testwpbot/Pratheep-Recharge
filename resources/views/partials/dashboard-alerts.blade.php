@if(!empty($dashboardAlerts) && $dashboardAlerts->isNotEmpty())
  <div class="hpr-alert-pop" id="hprAlertPop" hidden>
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

    function show(n){
      slides.forEach(function(s, idx){ s.hidden = idx !== n; });
      i = n;
      pop.hidden = false;
      pop.setAttribute('aria-hidden', 'false');
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
