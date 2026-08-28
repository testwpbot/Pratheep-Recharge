<script>
(function(){
  if (window.__hprDdReady) return;
  window.__hprDdReady = true;
  function closeAll(except){
    document.querySelectorAll('[data-hpr-dd].is-open').forEach(function(dd){
      if (dd === except) return;
      dd.classList.remove('is-open');
      var m = dd.querySelector('.hpr-dd__menu');
      if (m) m.hidden = true;
    });
  }
  function place(dd){
    var menu = dd.querySelector('.hpr-dd__menu');
    var btn = dd.querySelector('.hpr-dd__btn');
    if (!menu || !btn) return;
    var r = btn.getBoundingClientRect();
    var w = Math.min(Math.max(r.width, 180), window.innerWidth - 16);
    menu.style.position = 'fixed';
    menu.style.minWidth = w + 'px';
    menu.style.maxWidth = (window.innerWidth - 16) + 'px';
    menu.style.width = w + 'px';
    var left = r.left;
    if (left + w > window.innerWidth - 8) left = Math.max(8, window.innerWidth - w - 8);
    if (left < 8) left = 8;
    menu.style.left = left + 'px';
    menu.style.top = (r.bottom + 6) + 'px';
    menu.style.right = 'auto';
    var space = window.innerHeight - r.bottom - 16;
    if (space < 160){
      menu.style.top = 'auto';
      menu.style.bottom = (window.innerHeight - r.top + 6) + 'px';
      menu.style.maxHeight = Math.max(140, r.top - 16) + 'px';
    } else {
      menu.style.bottom = 'auto';
      menu.style.maxHeight = Math.min(280, space) + 'px';
    }
  }
  document.addEventListener('click', function(e){
    var btn = e.target.closest('[data-hpr-dd] .hpr-dd__btn');
    var item = e.target.closest('[data-hpr-dd] .hpr-dd__item');
    if (btn){
      e.preventDefault(); e.stopPropagation();
      var dd = btn.closest('[data-hpr-dd]');
      var open = dd.classList.contains('is-open');
      closeAll();
      if (!open){
        dd.classList.add('is-open');
        var menu = dd.querySelector('.hpr-dd__menu');
        if (menu){ menu.hidden = false; place(dd); }
      }
      return;
    }
    if (item){
      e.preventDefault(); e.stopPropagation();
      var dd = item.closest('[data-hpr-dd]');
      var val = item.getAttribute('data-value');
      var label = item.getAttribute('data-label') || item.textContent.trim();
      var hidden = dd.querySelector('input[type=hidden]');
      if (hidden) hidden.value = val;
      var lab = dd.querySelector('.hpr-dd__label');
      var preview = item.querySelector('[data-dd-preview]');
      if (lab){
        if (preview) lab.innerHTML = preview.outerHTML;
        else lab.textContent = label;
      }
      dd.querySelectorAll('.hpr-dd__item').forEach(function(i){ i.classList.toggle('is-active', i === item); });
      if (hidden) hidden.dispatchEvent(new Event('change', {bubbles:true}));
      closeAll();
      var formId = dd.getAttribute('data-auto-submit');
      if (formId){
        var form = document.getElementById(formId) || dd.closest('form');
        if (form) form.submit();
      }
      return;
    }
    closeAll();
  });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeAll(); });
  window.addEventListener('resize', function(){ closeAll(); });
  window.addEventListener('scroll', function(){ closeAll(); }, true);
})();
</script>
