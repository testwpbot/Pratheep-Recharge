{{-- Visible phone height for the dashboard / admin drawer.
     100vh / top:0;bottom:0 is taller than the real screen on mobile
     (address bar), which hid the account block. --}}
@once
<script>
(function(){
  function hprSetVvh(){
    var h = window.innerHeight || 0;
    if (window.visualViewport && visualViewport.height) {
      var vv = Math.round(visualViewport.height);
      h = h ? Math.min(h, vv) : vv;
    }
    if (h > 0) {
      document.documentElement.style.setProperty('--hpr-vvh', h + 'px');
    }
    var sb = document.getElementById('sidebar');
    if (!sb) return;
    if (window.matchMedia && window.matchMedia('(max-width: 900px)').matches && h > 0) {
      sb.style.height = h + 'px';
      sb.style.maxHeight = h + 'px';
    } else {
      sb.style.height = '';
      sb.style.maxHeight = '';
    }
  }
  window.hprSetVvh = hprSetVvh;
  hprSetVvh();
  document.addEventListener('DOMContentLoaded', hprSetVvh);
  window.addEventListener('resize', hprSetVvh);
  window.addEventListener('orientationchange', function(){ setTimeout(hprSetVvh, 80); });
  window.addEventListener('pageshow', hprSetVvh);
  if (window.visualViewport) visualViewport.addEventListener('resize', hprSetVvh);
})();
</script>
@endonce
