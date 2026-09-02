@extends('layouts.admin')
@section('title', 'Settings')

@push('styles')
<style>
  .slide-preview{margin-top:14px; padding:12px; background:#f7f9fd; border:1px dashed var(--line,#d5deee); border-radius:12px;}
  .slide-preview__label{font-size:12.5px; font-weight:700; color:var(--muted); margin-bottom:10px;}
  .slide-preview__grid, .slide-grid{display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:12px;}
  .slide-preview__cell{display:flex; flex-direction:column; gap:5px; font-size:11px; color:var(--muted); word-break:break-all;}
  .slide-preview__cell img{width:100%; aspect-ratio:3/4; object-fit:contain; background:#fff; border:1px solid var(--line,#e6ebf3); border-radius:8px;}
  .slide-item{position:relative; border:1px solid var(--line,#e6ebf3); border-radius:10px; overflow:hidden; background:#fff;}
  .slide-item img{width:100%; aspect-ratio:3/4; object-fit:contain; display:block; background:#f2f5fa;}
  .slide-item__remove{position:absolute; top:6px; right:6px; width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center;
    border:none; border-radius:999px; background:rgba(192,57,43,.92); color:#fff; cursor:pointer; box-shadow:0 2px 6px rgba(0,0,0,.2);}
  .slide-item__remove:hover{background:#c0392b;}
</style>
@endpush

@section('content')

<div class="set-hero">
  <div>
    <h3>Site settings</h3>
    <p>Banks, SEO, WhatsApp, email and who can run the admin panel.</p>
  </div>
</div>

<div class="set-tabs" role="tablist">
  <button type="button" class="set-tab active" data-set-tab="general">General</button>
  <button type="button" class="set-tab" data-set-tab="whatsapp">WhatsApp</button>
  <button type="button" class="set-tab" data-set-tab="bank">Bank accounts</button>
  <button type="button" class="set-tab" data-set-tab="seo">SEO</button>
  <button type="button" class="set-tab" data-set-tab="slideshow">Plans slideshow</button>
  <button type="button" class="set-tab" data-set-tab="smtp">Email / SMTP</button>
  @if($isMainAdmin)
    <button type="button" class="set-tab" data-set-tab="admins">Admins</button>
  @endif
</div>

{{-- ===== GENERAL ===== --}}
<div class="set-panel active" data-set-panel="general">
  <div class="card">
    <div class="card__head">
      <h3>General</h3>
      <small style="color:var(--muted); font-weight:600;">Name and support details shown to customers.</small>
    </div>
    <form method="POST" action="{{ route('admin.settings.general') }}" data-ajax>
      @csrf
      <div class="form-grid">
        <div class="field">
          <label>Site name</label>
          <input type="text" name="site_name" value="{{ old('site_name', $general['site_name'] ?? 'Happy Pratheep Recharge') }}" required>
        </div>
        <div class="field">
          <label>Support email</label>
          <input type="email" name="support_email" value="{{ old('support_email', $general['support_email'] ?? '') }}" placeholder="admin@happypratheep.lk">
          <div class="hint">Deposit requests and “provider money is low” emails go here.</div>
        </div>
        <div class="field">
          <label>Support phone</label>
          <input type="text" name="support_phone" value="{{ old('support_phone', $general['support_phone'] ?? '') }}" placeholder="+94 77 123 4567">
        </div>
        <div class="field">
          <label>Smallest wallet amount (LKR)</label>
          <input type="number" name="min_wallet_balance" min="0" max="10000" step="1"
                 value="{{ old('min_wallet_balance', $general['min_wallet_balance'] ?? 100) }}" required>
          <div class="hint">This amount must stay in the wallet after a recharge. Example: LKR 100 reserve + LKR 50 recharge = LKR 150 needed. Default 100.</div>
        </div>
        <div class="field">
          <label>DTH rate — INR to LKR</label>
          <input type="number" name="dth_inr_rate" min="0.01" max="100" step="0.01"
                 value="{{ old('dth_inr_rate', $general['dth_inr_rate'] ?? '3.65') }}" required>
          <div class="hint">How many LKR one Indian Rupee is worth. The customer pays in LKR; the DTH provider is credited the INR equivalent (LKR ÷ this rate). Example: LKR 1,825 ÷ 3.65 = 500 INR credited.</div>
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Deposit note (shown to customers)</label>
          <textarea name="deposit_note" rows="3">{{ old('deposit_note', $general['deposit_note'] ?? '') }}</textarea>
        </div>
      </div>
      <div style="margin-top:18px; display:flex; justify-content:flex-end;">
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="check" :size="14"/> Save general</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ===== WHATSAPP ===== --}}
<div class="set-panel" data-set-panel="whatsapp">
  <div class="card">
    <div class="card__head">
      <h3>WhatsApp button</h3>
      <small style="color:var(--muted); font-weight:600;">Green chat button on the bottom-right of the website. Not on this admin panel.</small>
    </div>
    <form method="POST" action="{{ route('admin.settings.whatsapp') }}">
      @csrf
      <div class="form-grid">
        <div class="field">
          <label>Show the button?</label>
          <label class="sw" style="margin-top:8px;">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" {{ old('enabled', $whatsapp['enabled'] ?? false) ? 'checked' : '' }}>
            <span class="sw__slider"></span>
          </label>
          <div class="hint">Turn off to hide it from customers.</div>
        </div>
        <div class="field">
          <label>WhatsApp number</label>
          <input type="text" name="phone" value="{{ old('phone', $whatsapp['phone'] ?? '') }}" placeholder="0771234567">
          <div class="hint">Sri Lanka numbers can start with 07. Or type the full number with country code, like 94771234567.</div>
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>First message</label>
          <textarea name="message" rows="3" maxlength="500" placeholder="Hi Happy Pratheep, I need help with a recharge.">{{ old('message', $whatsapp['message'] ?? '') }}</textarea>
          <div class="hint">This text is already typed when they open the chat. They can still edit it.</div>
        </div>
      </div>
      <div style="margin-top:18px; display:flex; justify-content:flex-end;">
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="check" :size="14"/> Save WhatsApp</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ===== BANKS ===== --}}
<div class="set-panel" data-set-panel="bank">
  <div class="bank-grid">
    @forelse ($banks as $acc)
      <div class="card bank-card">
        <div class="bank-card__head">
          <img class="bank-card__logo" src="{{ $acc->logoUrl() }}" alt="">
          <div>
            <b>{{ $acc->bank_name }}</b>
            <small>{{ $acc->is_active ? 'Shown to customers' : 'Hidden' }}</small>
          </div>
          <form method="POST" action="{{ route('admin.settings.banks.destroy', $acc) }}" onsubmit="return confirm('Remove this bank account?');">
            @csrf
            @method('DELETE')
            <button class="btn-admin btn-admin--danger btn-admin--sm" type="submit">Remove</button>
          </form>
        </div>
        @include('admin.settings._bank-form', ['account' => $acc, 'bankCatalog' => $bankCatalog])
      </div>
    @empty
      <div class="card">
        <p style="margin:0; color:var(--muted); font-weight:600;">No bank accounts yet. Add one so customers know where to send money.</p>
      </div>
    @endforelse

    <div class="card bank-card bank-card--add">
      <div class="card__head">
        <h3>Add another bank</h3>
      </div>
      @include('admin.settings._bank-form', ['account' => null, 'bankCatalog' => $bankCatalog])
    </div>
  </div>
</div>

{{-- ===== SEO ===== --}}
<div class="set-panel" data-set-panel="seo">
  <div class="card">
    <div class="card__head">
      <h3>SEO</h3>
      <small style="color:var(--muted); font-weight:600;">How Google and social apps show this website. Boxes below already show what is saved now.</small>
    </div>
    <form method="POST" action="{{ route('admin.settings.seo') }}" enctype="multipart/form-data">
      @csrf
      <div class="form-grid">
        <div class="field" style="grid-column:1/-1;">
          <label>Page title</label>
          <input type="text" name="meta_title" maxlength="70" value="{{ old('meta_title', $seo['meta_title'] ?? '') }}" placeholder="Happy Pratheep Recharge — Mobile reloads in Sri Lanka">
          <div class="hint">About 50–60 characters works best.</div>
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Short description</label>
          <textarea name="meta_description" rows="3" maxlength="180">{{ old('meta_description', $seo['meta_description'] ?? '') }}</textarea>
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Keywords</label>
          <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $seo['meta_keywords'] ?? '') }}" placeholder="reload, Dialog, Mobitel, electricity bill">
        </div>
        <div class="field">
          <label>Share title (Facebook / WhatsApp)</label>
          <input type="text" name="og_title" value="{{ old('og_title', $seo['og_title'] ?? '') }}">
        </div>
        <div class="field">
          <label>Show in Google?</label>
          <div class="hpr-dd" data-hpr-dd>
            <input type="hidden" name="robots" value="{{ old('robots', $seo['robots'] ?? 'index') }}">
            <button type="button" class="hpr-dd__btn">
              <span class="hpr-dd__label">{{ (old('robots', $seo['robots'] ?? 'index') === 'noindex') ? 'Hide from Google' : 'Show in Google' }}</span>
              <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="hpr-dd__menu" hidden>
              <button type="button" class="hpr-dd__item {{ ($seo['robots'] ?? 'index')!=='noindex' ? 'is-active' : '' }}" data-value="index" data-label="Show in Google">Show in Google</button>
              <button type="button" class="hpr-dd__item {{ ($seo['robots'] ?? '')==='noindex' ? 'is-active' : '' }}" data-value="noindex" data-label="Hide from Google">Hide from Google</button>
            </div>
          </div>
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Share description</label>
          <textarea name="og_description" rows="2">{{ old('og_description', $seo['og_description'] ?? '') }}</textarea>
        </div>
        <div class="field">
          <label>Share image URL</label>
          <input type="url" name="og_image_url" value="{{ old('og_image_url', $seo['og_image_url'] ?? '') }}" placeholder="https://…">
        </div>
        <div class="field">
          <label>Or upload share image</label>
          @php
            $ogPreview = old('og_image_url', $seo['og_image_url'] ?? '');
            if ($ogPreview === '' && !empty($seo['og_image_path'])) {
                $ogPreview = asset($seo['og_image_path']);
            }
          @endphp
          @include('admin.settings._file-picker', [
            'name' => 'og_image',
            'current' => $ogPreview,
            'button' => 'Choose share image',
            'hint' => 'Shown on Facebook / WhatsApp. PNG or JPG · max 2MB',
          ])
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Website icon (favicon)</label>
          @php
            $favPreview = '';
            if (!empty($seo['favicon_path'])) {
                $favPreview = asset($seo['favicon_path']);
            } else {
                $favPreview = asset('assets/logo-mark.png');
            }
          @endphp
          @include('admin.settings._file-picker', [
            'name' => 'favicon',
            'current' => $favPreview,
            'button' => 'Upload favicon',
            'hint' => 'Small square icon in the browser tab. PNG, ICO or SVG · max 1MB',
            'accept' => 'image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon,.ico',
          ])
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Google site verification code</label>
          <input type="text" name="google_site_verification" value="{{ old('google_site_verification', $seo['google_site_verification'] ?? '') }}" placeholder="Paste the content= value from Google">
        </div>
      </div>
      <div style="margin-top:18px; display:flex; justify-content:flex-end;">
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="check" :size="14"/> Save SEO</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ===== PLANS SLIDESHOW ===== --}}
<div class="set-panel" data-set-panel="slideshow">
  <div class="card">
    <div class="card__head">
      <h3>Plans & Rates slideshow</h3>
      <small style="color:var(--muted); font-weight:600;">Images shown at the top of the customer Plans &amp; Rates page. Portrait posters work best; the whole image is always shown.</small>
    </div>

    <form method="POST" action="{{ route('admin.settings.plan-slides.store') }}" enctype="multipart/form-data" id="slideUploadForm">
      @csrf
      <div class="field">
        <label>Upload images</label>
        <input type="file" name="images[]" id="slideInput" accept="image/png,image/jpeg,image/webp" multiple>
        <div class="hint">You can select several images at once. JPG, PNG or WebP, up to 5 MB each. New images are added to the ones already there.</div>
        @error('images')<div class="hint" style="color:var(--danger,#c0392b); font-weight:700;">{{ $message }}</div>@enderror
        @error('images.*')<div class="hint" style="color:var(--danger,#c0392b); font-weight:700;">{{ $message }}</div>@enderror
      </div>

      {{-- Live preview of the files the admin just picked (before uploading) --}}
      <div id="slidePreview" class="slide-preview" hidden>
        <div class="slide-preview__label">Selected images (not uploaded yet):</div>
        <div class="slide-preview__grid" id="slidePreviewGrid"></div>
      </div>

      <div style="margin-top:16px; display:flex; gap:10px; justify-content:flex-end;">
        <button type="submit" class="btn-admin btn-admin--gold" id="slideUploadBtn" disabled>
          <span class="btn-label"><x-icon name="check" :size="14"/> Upload images</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>

    <hr style="margin:22px 0; border:none; border-top:1px solid var(--line, #e6ebf3);">

    <div class="card__head" style="margin-bottom:12px;">
      <h3 style="font-size:15px;">Current images</h3>
      <small style="color:var(--muted); font-weight:600;">{{ count($planSlides) }} image{{ count($planSlides) === 1 ? '' : 's' }} live on the Plans page.</small>
    </div>

    @if (empty($planSlides))
      <p style="margin:0; color:var(--muted); font-weight:600;">No slideshow images yet. Upload some above — the slideshow stays hidden on the Plans page until at least one image is added.</p>
    @else
      <div class="slide-grid">
        @foreach ($planSlides as $slide)
          <div class="slide-item">
            <img src="{{ asset($slide) }}" alt="Slide" loading="lazy">
            <form method="POST" action="{{ route('admin.settings.plan-slides.destroy') }}"
                  onsubmit="return confirm('Remove this image from the slideshow?');">
              @csrf
              @method('DELETE')
              <input type="hidden" name="path" value="{{ $slide }}">
              <button type="submit" class="slide-item__remove" title="Remove image" aria-label="Remove image">
                <x-icon name="x" :size="14"/>
              </button>
            </form>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>

{{-- ===== SMTP ===== --}}
<div class="set-panel" data-set-panel="smtp">
  <div class="card">
    <div class="card__head">
      <h3>Email / SMTP</h3>
      <small style="color:var(--muted); font-weight:600;">Used for OTP, deposits and alerts. Leave password blank to keep the current one.</small>
    </div>
    <form method="POST" action="{{ route('admin.settings.smtp') }}" data-ajax id="smtpForm">
      @csrf
      <div class="form-grid">
        <div class="field" style="grid-column:span 2;">
          <label>SMTP host <span class="req">*</span></label>
          <input type="text" name="host" value="{{ old('host', $smtp['host'] ?? '') }}" required>
        </div>
        <div class="field">
          <label>Port <span class="req">*</span></label>
          <input type="number" name="port" value="{{ old('port', $smtp['port'] ?? 587) }}" required min="1" max="65535">
        </div>
        <div class="field">
          <label>Encryption</label>
          <div class="hpr-dd" data-hpr-dd>
            <input type="hidden" name="encryption" value="{{ old('encryption', $smtp['encryption'] ?? 'tls') }}">
            <button type="button" class="hpr-dd__btn">
              <span class="hpr-dd__label">{{ strtoupper(old('encryption', $smtp['encryption'] ?? 'tls')) }}</span>
              <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="hpr-dd__menu" hidden>
              @foreach (['tls'=>'TLS','ssl'=>'SSL','none'=>'None'] as $k=>$v)
                <button type="button" class="hpr-dd__item {{ (old('encryption', $smtp['encryption'] ?? 'tls'))===$k ? 'is-active' : '' }}" data-value="{{ $k }}" data-label="{{ $v }}">{{ $v }}</button>
              @endforeach
            </div>
          </div>
        </div>
        <div class="field">
          <label>SMTP username</label>
          <input type="text" name="username" value="{{ old('username', $smtp['username'] ?? '') }}" autocomplete="off">
        </div>
        <div class="field">
          <label>SMTP password</label>
          <input type="password" name="password" placeholder="Leave blank to keep current" autocomplete="new-password">
        </div>
        <div class="field">
          <label>From address <span class="req">*</span></label>
          <input type="email" name="from_address" value="{{ old('from_address', $smtp['from_address'] ?? 'noreply@happypratheep.lk') }}" required>
        </div>
        <div class="field">
          <label>From name <span class="req">*</span></label>
          <input type="text" name="from_name" value="{{ old('from_name', $smtp['from_name'] ?? 'Happy Pratheep Recharge') }}" required>
        </div>
      </div>

      <div class="test-smtp-row">
        <input type="email" id="testTo" placeholder="Send test email to…">
        <button type="button" class="btn-admin btn-admin--ghost btn-admin--sm" id="testSmtpBtn">
          <span class="btn-label"><x-icon name="mail" :size="13"/> Send test</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
      <div class="hint" id="testSmtpResult" style="margin-top:8px;"></div>

      <div style="margin-top:18px; display:flex; justify-content:flex-end;">
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="check" :size="14"/> Save email settings</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>
  </div>
</div>

@if($isMainAdmin)
<div class="set-panel" data-set-panel="admins">
  <div class="card">
    <div class="card__head">
      <h3>Admins</h3>
      <small style="color:var(--muted); font-weight:600;">Main admin can add people. Admin can run the site but cannot add other admins.</small>
    </div>

    <form method="POST" action="{{ route('admin.settings.admins.store') }}" class="admin-add">
      @csrf
      <div class="form-grid">
        <div class="field">
          <label>Name <span class="req">*</span></label>
          <input type="text" name="name" value="{{ old('name') }}" required>
        </div>
        <div class="field">
          <label>Email <span class="req">*</span></label>
          <input type="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div class="field">
          <label>Phone <span class="req">*</span></label>
          <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="0771234567" required>
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Needed for a new person">
        </div>
        <div class="field">
          <label>Role <span class="req">*</span></label>
          <div class="hpr-dd" data-hpr-dd>
            <input type="hidden" name="role" value="{{ old('role', 'admin') }}">
            <button type="button" class="hpr-dd__btn">
              <span class="hpr-dd__label">{{ old('role','admin')==='main' ? 'Main admin' : 'Admin' }}</span>
              <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            <div class="hpr-dd__menu" hidden>
              <button type="button" class="hpr-dd__item {{ old('role','admin')==='admin' ? 'is-active' : '' }}" data-value="admin" data-label="Admin">Admin</button>
              <button type="button" class="hpr-dd__item {{ old('role')==='main' ? 'is-active' : '' }}" data-value="main" data-label="Main admin">Main admin</button>
            </div>
          </div>
        </div>
      </div>
      <div style="margin-top:16px; display:flex; justify-content:flex-end;">
        <button type="submit" class="btn-admin btn-admin--gold">
          <span class="btn-label"><x-icon name="plus" :size="14"/> Add admin</span>
          <span class="btn-spinner" hidden></span>
        </button>
      </div>
    </form>

    <div class="table-wrap" style="margin-top:20px;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th class="col-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($admins as $a)
          <tr>
            <td>
              <b>{{ $a->name }}</b>
              @if($a->id === auth()->id())<br><small style="color:var(--muted);">you</small>@endif
            </td>
            <td>{{ $a->email }}</td>
            <td>{{ $a->phone }}</td>
            <td>
              <form method="POST" action="{{ route('admin.settings.admins.update', $a) }}">
                @csrf
                @method('PATCH')
                <div class="hpr-dd hpr-dd--sm" data-hpr-dd data-auto-submit="1">
                  <input type="hidden" name="role" value="{{ $a->isMainAdmin() ? 'main' : 'admin' }}">
                  <button type="button" class="hpr-dd__btn">
                    <span class="hpr-dd__label">{{ $a->adminRoleLabel() }}</span>
                    <svg class="hpr-dd__caret" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                  </button>
                  <div class="hpr-dd__menu" hidden>
                    <button type="button" class="hpr-dd__item {{ !$a->isMainAdmin() ? 'is-active' : '' }}" data-value="admin" data-label="Admin">Admin</button>
                    <button type="button" class="hpr-dd__item {{ $a->isMainAdmin() ? 'is-active' : '' }}" data-value="main" data-label="Main admin">Main admin</button>
                  </div>
                </div>
              </form>
            </td>
            <td class="col-actions">
              <div class="td-actions">
                @if($a->id !== auth()->id())
                  <form method="POST" action="{{ route('admin.settings.admins.destroy', $a) }}" onsubmit="return confirm('Remove admin access for {{ addslashes($a->name) }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-admin btn-admin--danger btn-admin--sm">Remove</button>
                  </form>
                @else
                  <em style="color:var(--muted); font-size:12px;">Your account</em>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center; color:var(--muted); padding:24px;">No admins yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script>
(function(){
  var tabs = document.querySelectorAll('.set-tab');
  var panels = document.querySelectorAll('.set-panel');
  function openTab(key){
    var found = false;
    tabs.forEach(function(x){
      var on = x.dataset.setTab === key;
      x.classList.toggle('active', on);
      if (on) found = true;
    });
    if (!found) return;
    panels.forEach(function(p){ p.classList.toggle('active', p.dataset.setPanel === key); });
  }
  tabs.forEach(function(t){ t.addEventListener('click', function(){ openTab(t.dataset.setTab); }); });
  var q = new URLSearchParams(window.location.search).get('tab');
  if (q) openTab(q);

  // ----- Plans slideshow: multi-image preview before upload -----
  (function(){
    var input = document.getElementById('slideInput');
    var box = document.getElementById('slidePreview');
    var grid = document.getElementById('slidePreviewGrid');
    var btn = document.getElementById('slideUploadBtn');
    if (!input || !grid) return;
    input.addEventListener('change', function(){
      grid.innerHTML = '';
      var files = Array.prototype.slice.call(input.files || []);
      if (!files.length){
        box.hidden = true;
        if (btn) btn.disabled = true;
        return;
      }
      files.forEach(function(f){
        if (!f.type || f.type.indexOf('image/') !== 0) return;
        var cell = document.createElement('div');
        cell.className = 'slide-preview__cell';
        var img = document.createElement('img');
        img.alt = f.name;
        var reader = new FileReader();
        reader.onload = function(ev){ img.src = ev.target.result; };
        reader.readAsDataURL(f);
        var cap = document.createElement('span');
        cap.textContent = f.name;
        cell.appendChild(img);
        cell.appendChild(cap);
        grid.appendChild(cell);
      });
      box.hidden = false;
      if (btn) btn.disabled = false;
    });
  })();

  function setFilePreview(box, src){
    if (!box || !src) return;
    var hold = box.querySelector('.hpr-file__preview');
    if (!hold) return;
    var img = hold.querySelector('img');
    if (!img){
      hold.innerHTML = '';
      img = document.createElement('img');
      img.alt = '';
      hold.appendChild(img);
    }
    img.src = src;
    hold.classList.remove('is-empty');
    var ph = hold.querySelector('.hpr-file__ph');
    if (ph) ph.remove();
  }

  document.querySelectorAll('[data-hpr-file]').forEach(function(box){
    var input = box.querySelector('input[type=file]');
    var nameEl = box.querySelector('.hpr-file__name');
    if (!input) return;
    input.addEventListener('change', function(){
      var f = input.files && input.files[0];
      if (!f) return;
      if (nameEl) nameEl.textContent = f.name;
      if (!f.type || f.type.indexOf('image/') !== 0) return;
      var reader = new FileReader();
      reader.onload = function(ev){ setFilePreview(box, ev.target.result); };
      reader.readAsDataURL(f);
    });
  });

  document.querySelectorAll('input[name=logo_url], input[name=og_image_url]').forEach(function(inp){
    inp.addEventListener('input', function(){
      var val = (inp.value || '').trim();
      if (!/^https?:\/\//i.test(val)) return;
      var form = inp.closest('form');
      var box = form ? form.querySelector('[data-hpr-file]') : null;
      setFilePreview(box, val);
    });
  });

  document.querySelectorAll('[data-bank-picker]').forEach(function(dd){
    dd.addEventListener('click', function(e){
      var item = e.target.closest('.hpr-dd__item');
      if (!item) return;
      var form = dd.closest('form');
      if (!form) return;
      var slug = item.getAttribute('data-value');
      var name = item.getAttribute('data-bank-name') || '';
      var logo = item.getAttribute('data-logo') || '';
      var hiddenName = form.querySelector('.bank-name-hidden');
      var visName = form.querySelector('.bank-custom-name-input');
      form.querySelectorAll('.bank-custom-only').forEach(function(el){
        el.style.display = slug === 'custom' ? '' : 'none';
      });
      if (hiddenName) hiddenName.value = name;
      if (visName){
        visName.required = slug === 'custom';
        if (slug !== 'custom') visName.value = name;
      }
      var card = form.closest('.bank-card');
      var headImg = card ? card.querySelector('.bank-card__logo') : null;
      if (headImg && logo) headImg.src = logo;
      var fileInput = form.querySelector('[data-hpr-file] input[type=file]');
      if (logo && (!fileInput || !fileInput.files || !fileInput.files[0])){
        setFilePreview(form.querySelector('[data-hpr-file]'), logo);
      }
    });
    var form = dd.closest('form');
    var visName = form ? form.querySelector('.bank-custom-name-input') : null;
    var hiddenName = form ? form.querySelector('.bank-name-hidden') : null;
    if (visName && hiddenName){
      visName.addEventListener('input', function(){ hiddenName.value = visName.value; });
    }
  });

  var testBtn = document.getElementById('testSmtpBtn');
  var testTo  = document.getElementById('testTo');
  var result  = document.getElementById('testSmtpResult');
  if (testBtn){
    testBtn.addEventListener('click', function(){
      var to = testTo.value.trim();
      if (!to){ testTo.focus(); return; }
      var form = document.getElementById('smtpForm');
      var fd = new FormData(form);
      fd.append('to', to);
      var started = performance.now();
      var MIN_MS = 2200;
      if (typeof setBtnLoading === 'function') setBtnLoading(testBtn, true);
      result.textContent = 'Sending…'; result.style.color = 'var(--muted)';
      fetch('{{ route('admin.settings.test-smtp') }}', {
        method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, credentials:'same-origin'
      })
      .then(function(r){ return r.json().then(function(d){ return {ok:r.ok, d:d}; }); })
      .then(function(res){
        setTimeout(function(){
          if (typeof setBtnLoading === 'function') setBtnLoading(testBtn, false);
          if (res.ok && res.d.ok){ result.textContent = res.d.message || 'Test email sent.'; result.style.color = '#1c7a49'; }
          else { result.textContent = (res.d && res.d.message) || 'Test failed.'; result.style.color = '#b42f2f'; }
        }, Math.max(0, MIN_MS - (performance.now() - started)));
      })
      .catch(function(e){
        setTimeout(function(){
          if (typeof setBtnLoading === 'function') setBtnLoading(testBtn, false);
          result.textContent = e.message; result.style.color = '#b42f2f';
        }, Math.max(0, MIN_MS - (performance.now() - started)));
      });
    });
  }
})();
</script>
@endpush
