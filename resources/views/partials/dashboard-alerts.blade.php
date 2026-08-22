@if(!empty($dashboardAlerts) && $dashboardAlerts->isNotEmpty())
  <div class="hpr-alerts" id="hprAlerts">
    @foreach ($dashboardAlerts as $alert)
      @include('partials.dashboard-alert', ['alert' => $alert])
    @endforeach
  </div>
  <script>
  (function(){
    document.querySelectorAll('[data-alert-dismiss]').forEach(function(form){
      form.addEventListener('submit', function(e){
        e.preventDefault();
        var banner = form.closest('.hpr-alert');
        if (banner){
          banner.classList.add('is-hiding');
          setTimeout(function(){ banner.remove(); }, 220);
        }
        fetch(form.action, {
          method:'POST',
          headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN': (form.querySelector('[name=_token]')||{}).value || ''},
          credentials:'same-origin',
          body: new FormData(form)
        }).catch(function(){});
      });
    });
  })();
  </script>
@endif
