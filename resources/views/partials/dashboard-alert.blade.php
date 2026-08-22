@php
  $preview = !empty($preview);
  $theme = ($alert->theme ?? 'navy') === 'gold' ? 'gold' : 'navy';
  $img = $alert->imageUrl();
  $btn1 = trim((string) ($alert->button_label ?? ''));
  $btn2 = trim((string) ($alert->button2_label ?? ''));
  $url1 = \App\Models\Alert::safeUrl($alert->button_url ?? null) ?: route('wallet');
  $url2 = \App\Models\Alert::safeUrl($alert->button2_url ?? null) ?: route('dashboard.plans');
@endphp
<article class="hpr-alert hpr-alert--{{ $theme }}" @if(!$preview && $alert->id) data-alert-id="{{ $alert->id }}" @endif>
  @if($img || $preview)
    <div class="hpr-alert__media" data-pv="media" @if(!$img) hidden @endif>
      <img src="{{ $img ?: asset('assets/logo-mark.png') }}" alt="" data-pv="img" @if(!$img) hidden @endif>
    </div>
  @endif
  <div class="hpr-alert__body">
    <p class="hpr-alert__eyebrow" data-pv="eyebrow">{{ $alert->eyebrow ?: 'Notice' }}</p>
    <h3 class="hpr-alert__heading" data-pv="heading">{{ $alert->heading ?: 'Your heading' }}</h3>
    @if($alert->body || $preview)
      <p class="hpr-alert__text" data-pv="body">{{ $alert->body ?: 'Your message will show here.' }}</p>
    @endif
    @if($btn1 || $btn2 || $preview)
      <div class="hpr-alert__actions">
        <a href="{{ $preview ? '#' : $url1 }}" class="hpr-alert__btn hpr-alert__btn--main" data-pv="btn1" @if(!$btn1 && $preview) hidden @endif>{{ $btn1 ?: 'Button' }}</a>
        <a href="{{ $preview ? '#' : $url2 }}" class="hpr-alert__btn hpr-alert__btn--ghost" data-pv="btn2" @if(!$btn2) hidden @endif>{{ $btn2 ?: 'Button' }}</a>
      </div>
    @endif
  </div>
  @if(!$preview && $alert->is_dismissible)
    <form method="POST" action="{{ route('dashboard.alerts.dismiss', $alert) }}" class="hpr-alert__close-form" data-alert-dismiss>
      @csrf
      <button type="submit" class="hpr-alert__close" aria-label="Hide this notice">
        <x-icon name="x" :size="16"/>
      </button>
    </form>
  @endif
</article>
