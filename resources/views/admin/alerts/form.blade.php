@extends('layouts.admin')
@section('title', $alert->exists ? 'Edit alert' : 'New alert')

@section('content')

<div style="margin-bottom:16px;">
  <a href="{{ route('admin.alerts.index') }}" class="btn-admin btn-admin--ghost btn-admin--sm">← Back to alerts</a>
</div>

<div class="alert-admin-grid">
  <div class="card">
    <div class="card__head">
      <div>
        <h3>{{ $alert->exists ? 'Edit alert' : 'New alert' }}</h3>
        <small style="color:var(--muted); font-weight:600;">This opens as a popup on account pages after they sign in. Not on the homepage.</small>
      </div>
    </div>

    <form method="POST" action="{{ $alert->exists ? route('admin.alerts.update', $alert) : route('admin.alerts.store') }}" enctype="multipart/form-data" id="alertForm">
      @csrf
      @if($alert->exists) @method('PATCH') @endif

      <div class="form-grid">
        <div class="field">
          <label>Admin name <span class="req">*</span></label>
          <input type="text" name="title" value="{{ old('title', $alert->title) }}" required placeholder="e.g. Avurudu offer">
          <div class="hint">Only you see this in the admin list.</div>
        </div>
        <div class="field">
          <label>Small label</label>
          <input type="text" name="eyebrow" id="alertEyebrow" value="{{ old('eyebrow', $alert->eyebrow) }}" placeholder="e.g. Limited offer">
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Heading <span class="req">*</span></label>
          <input type="text" name="heading" id="alertHeading" value="{{ old('heading', $alert->heading) }}" required placeholder="e.g. Add LKR 1,000 and get extra cashback">
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Message</label>
          <textarea name="body" id="alertBody" rows="8" placeholder="Write the message. You can make text bold, add lists and links.">{{ old('body', $alert->body) }}</textarea>
          <div class="hint">Use the toolbar to style the text — bold, colour, lists, links.</div>
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Picture</label>
          @include('admin.settings._file-picker', [
            'name' => 'image',
            'current' => $alert->imageUrl(),
            'button' => 'Choose picture',
            'hint' => 'PNG or JPG · max 2MB. Looks best as a wide photo.',
          ])
          @if($alert->image_path)
            <label class="field-inline" style="margin-top:10px;">
              <input type="checkbox" name="remove_image" value="1">
              <span>Remove the current picture</span>
            </label>
          @endif
        </div>
        <div class="field">
          <label>Button text</label>
          <input type="text" name="button_label" id="alertBtn1" value="{{ old('button_label', $alert->button_label) }}" placeholder="e.g. Add money">
        </div>
        <div class="field">
          <label>Button link</label>
          <input type="text" name="button_url" value="{{ old('button_url', $alert->button_url) }}" placeholder="/wallet or https://…">
        </div>
        <div class="field">
          <label>Second button text</label>
          <input type="text" name="button2_label" id="alertBtn2" value="{{ old('button2_label', $alert->button2_label) }}" placeholder="Optional">
        </div>
        <div class="field">
          <label>Second button link</label>
          <input type="text" name="button2_url" value="{{ old('button2_url', $alert->button2_url) }}" placeholder="/plans">
        </div>
        <div class="field">
          <label>Look <span class="req">*</span></label>
          <div class="hpr-dd hpr-dd--block" data-hpr-dd>
            <input type="hidden" name="theme" id="alertTheme" value="{{ old('theme', $alert->theme ?: 'navy') }}">
            <button type="button" class="hpr-dd__btn">
              <span class="hpr-dd__label">{{ old('theme', $alert->theme) === 'gold' ? 'Gold' : 'Navy' }}</span>
              <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="hpr-dd__menu" hidden>
              <button type="button" class="hpr-dd__item {{ old('theme', $alert->theme) !== 'gold' ? 'is-active' : '' }}" data-value="navy" data-label="Navy">Navy</button>
              <button type="button" class="hpr-dd__item {{ old('theme', $alert->theme) === 'gold' ? 'is-active' : '' }}" data-value="gold" data-label="Gold">Gold</button>
            </div>
          </div>
        </div>
        <div class="field">
          <label>Who can see it <span class="req">*</span></label>
          <div class="hpr-dd hpr-dd--block" data-hpr-dd>
            <input type="hidden" name="audience" value="{{ old('audience', $alert->audience ?: 'all') }}">
            <button type="button" class="hpr-dd__btn">
              <span class="hpr-dd__label">
                @php $aud = old('audience', $alert->audience ?: 'all'); @endphp
                {{ $aud === 'retailers' ? 'Retailers' : ($aud === 'customers' ? 'Customers' : 'Everyone') }}
              </span>
              <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="hpr-dd__menu" hidden>
              <button type="button" class="hpr-dd__item {{ $aud==='all' ? 'is-active' : '' }}" data-value="all" data-label="Everyone">Everyone</button>
              <button type="button" class="hpr-dd__item {{ $aud==='customers' ? 'is-active' : '' }}" data-value="customers" data-label="Customers">Customers</button>
              <button type="button" class="hpr-dd__item {{ $aud==='retailers' ? 'is-active' : '' }}" data-value="retailers" data-label="Retailers">Retailers</button>
            </div>
          </div>
        </div>
        <div class="field">
          <label>Show from</label>
          <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $alert->starts_at?->timezone('Asia/Colombo')->format('Y-m-d\\TH:i')) }}">
        </div>
        <div class="field">
          <label>Show until</label>
          <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $alert->ends_at?->timezone('Asia/Colombo')->format('Y-m-d\\TH:i')) }}">
          <div class="hint">Leave both empty to show it all the time.</div>
        </div>
        <div class="field">
          <label>Order</label>
          <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $alert->sort_order ?? 0) }}">
          <div class="hint">Smaller number shows first.</div>
        </div>
        <div class="field">
          <label>Turn on?</label>
          <label class="sw" style="margin-top:8px;">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $alert->is_active) ? 'checked' : '' }}>
            <span class="sw__slider"></span>
          </label>
        </div>
        <div class="field">
          <label>Can they hide it?</label>
          <label class="sw" style="margin-top:8px;">
            <input type="hidden" name="is_dismissible" value="0">
            <input type="checkbox" name="is_dismissible" value="1" {{ old('is_dismissible', $alert->is_dismissible) ? 'checked' : '' }}>
            <span class="sw__slider"></span>
          </label>
          <div class="hint">If on, they can close the popup and it stays hidden.</div>
        </div>
      </div>

      <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
        <a href="{{ route('admin.alerts.index') }}" class="btn-admin btn-admin--ghost">Cancel</a>
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="check" :size="14"/> Save alert</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>

  <div>
    <p class="fund-subhead">How the popup looks</p>
    <div id="alertPreviewWrap" class="hpr-alert-pop is-preview">
      @include('partials.dashboard-alert', ['alert' => $alert, 'preview' => true])
    </div>
    <p style="margin:12px 0 0; color:var(--muted); font-size:12.5px; font-weight:600; line-height:1.5;">Customers see this as a popup card on Wallet, Plans, Orders and the other account pages — not stuck on the page.</p>
  </div>
</div>

@endsection

@push('styles')
<style>
.alert-admin-grid{
  display:grid; gap:20px; grid-template-columns:1.15fr .85fr; align-items:start;
}
@media (max-width:960px){
  .alert-admin-grid{grid-template-columns:1fr;}
}
.tox-tinymce{
  border:1.6px solid rgba(11,42,91,.16) !important;
  border-radius:12px !important;
  overflow:hidden;
}
.tox .tox-toolbar, .tox .tox-toolbar__overflow, .tox .tox-toolbar__primary{
  background:#f7f9fd !important;
}
.tox .tox-edit-area__iframe{background:#fff !important;}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function(){
  var form = document.getElementById('alertForm');
  if (!form) return;
  var banner = document.querySelector('#alertPreviewWrap .hpr-alert');
  if (!banner) return;
  var editor = null;

  function val(name){
    var el = form.querySelector('[name="'+name+'"]');
    return el ? (el.value || '').trim() : '';
  }
  function bodyHtml(){
    if (editor) return (editor.getContent() || '').trim();
    return val('body');
  }
  function setText(sel, text){
    var el = banner.querySelector(sel);
    if (!el) return;
    el.textContent = text;
  }
  function paint(){
    setText('[data-pv=eyebrow]', val('eyebrow') || 'Notice');
    setText('[data-pv=heading]', val('heading') || 'Your heading');
    var bodyEl = banner.querySelector('[data-pv=body]');
    if (bodyEl){
      var html = bodyHtml();
      bodyEl.innerHTML = html || 'Your message will show here.';
      bodyEl.hidden = false;
    }
    var b1 = banner.querySelector('[data-pv=btn1]');
    var b2 = banner.querySelector('[data-pv=btn2]');
    if (b1){ b1.textContent = val('button_label') || 'Button'; b1.hidden = !val('button_label'); }
    if (b2){ b2.textContent = val('button2_label') || 'Button'; b2.hidden = !val('button2_label'); }
    var theme = val('theme') || 'navy';
    banner.classList.toggle('hpr-alert--gold', theme === 'gold');
    banner.classList.toggle('hpr-alert--navy', theme !== 'gold');
  }

  if (window.tinymce){
    tinymce.init({
      selector: '#alertBody',
      license_key: 'gpl',
      base_url: 'https://cdn.jsdelivr.net/npm/tinymce@7.6.1',
      suffix: '.min',
      menubar: false,
      branding: false,
      promotion: false,
      height: 260,
      plugins: 'lists link autolink',
      toolbar: 'undo redo | blocks | bold italic underline | forecolor | alignleft aligncenter alignright | bullist numlist | link | removeformat',
      block_formats: 'Paragraph=p; Big heading=h2; Heading=h3; Small heading=h4',
      convert_urls: false,
      link_default_target: '_blank',
      link_assume_external_targets: true,
      content_style: 'body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;font-size:16px;line-height:1.65;color:#182033;padding:10px 8px;} p{margin:0 0 .75em;} p:last-child{margin-bottom:0;} h2{font-size:32px;line-height:1.2;font-weight:800;margin:0 0 .45em;letter-spacing:-.02em;} h3{font-size:24px;line-height:1.25;font-weight:800;margin:0 0 .45em;letter-spacing:-.02em;} h4{font-size:19px;line-height:1.3;font-weight:800;margin:0 0 .4em;}',
      setup: function(ed){
        editor = ed;
        ed.on('change keyup undo redo SetContent', function(){
          ed.save();
          paint();
        });
      }
    });
  }

  form.addEventListener('submit', function(){
    if (window.tinymce) tinymce.triggerSave();
  });
  form.addEventListener('input', paint);
  form.addEventListener('change', paint);
  document.querySelectorAll('#alertForm .hpr-dd__item').forEach(function(item){
    item.addEventListener('click', function(){ setTimeout(paint, 0); });
  });
  paint();

  var file = form.querySelector('input[name=image]');
  var img = banner.querySelector('[data-pv=img]');
  var media = banner.querySelector('[data-pv=media]');
  if (file && img){
    file.addEventListener('change', function(){
      var f = file.files && file.files[0];
      if (!f || !f.type || f.type.indexOf('image/') !== 0) return;
      var reader = new FileReader();
      reader.onload = function(ev){
        img.src = ev.target.result;
        img.hidden = false;
        if (media) media.hidden = false;
        var box = form.querySelector('[data-hpr-file] .hpr-file__preview');
        if (box){
          box.classList.remove('is-empty');
          var hold = box.querySelector('img');
          if (!hold){ hold = document.createElement('img'); hold.alt=''; box.innerHTML=''; box.appendChild(hold); }
          hold.src = ev.target.result;
        }
      };
      reader.readAsDataURL(f);
    });
  }
})();
</script>
@endpush
