@extends('layouts.app')

@section('title', ($section ?? 'Page') . ' · Happy Pratheep Recharge')

@section('content')
<section class="sec sec--light" style="min-height:60vh;display:flex;align-items:center;">
  <div class="wrap" style="text-align:center;max-width:640px;">
    <span class="sec__eyebrow"><i></i>Coming Soon<i></i></span>
    <h2 style="font-size:clamp(28px,4vw,42px);font-weight:800;color:var(--navy-900);letter-spacing:-.025em;line-height:1.15;">
      {{ $section ?? 'This page' }}
    </h2>
    <p style="color:var(--muted);margin-top:14px;font-size:16px;line-height:1.7;">
      We're wiring this section up in Laravel right now. The landing page and all its styles are fully preserved —
      additional pages like recharge forms, sign-in, and support will be added on top of this same design system.
    </p>
    <div style="margin-top:28px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="{{ route('home') }}" class="btn btn--gold">
        <x-icon name="home" :size="17"/>
        Back to Home
      </a>
      <a href="{{ route('support') }}" class="btn btn--ghost">
        <x-icon name="headset" :size="17"/>
        Contact Support
      </a>
    </div>
  </div>
</section>
@endsection
