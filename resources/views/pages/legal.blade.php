@extends('layouts.app')

@section('title', ($legalTitle ?? 'Help') . ' · Happy Pratheep Recharge')
@section('meta_description', $legalIntro ?? 'Help and policies for Happy Pratheep Recharge.')

@section('content')
<section class="sec sec--light legal-page">
  <div class="wrap">
    <div class="legal-hero">
      <span class="sec__eyebrow"><i></i>{{ $legalEyebrow ?? 'Help' }}<i></i></span>
      <h2>{{ $legalTitle ?? 'Help' }}</h2>
      <p class="sec__sub">{{ $legalIntro ?? '' }}</p>
      <small class="legal-updated">Last updated: 23 August 2026</small>
    </div>

    <div class="legal-grid">
      <aside class="legal-side">
        <nav>
          <a href="{{ route('support') }}" class="{{ request()->routeIs('support') ? 'is-on' : '' }}">Support</a>
          <a href="{{ route('privacy') }}" class="{{ request()->routeIs('privacy') ? 'is-on' : '' }}">Privacy Policy</a>
          <a href="{{ route('terms') }}" class="{{ request()->routeIs('terms') ? 'is-on' : '' }}">Terms of Service</a>
          <a href="{{ route('refund') }}" class="{{ request()->routeIs('refund') ? 'is-on' : '' }}">Refund Policy</a>
        </nav>
      </aside>
      <article class="legal-body">
        @yield('legal')
      </article>
    </div>
  </div>
</section>
@endsection

@push('styles')
<style>
.legal-page{padding-top:36px; padding-bottom:72px;}
.legal-hero{max-width:720px; margin-bottom:28px;}
.legal-hero h2{margin:8px 0 10px; font-size:clamp(28px,4vw,40px); font-weight:800; color:var(--navy-900); letter-spacing:-.03em; line-height:1.15;}
.legal-updated{display:block; margin-top:10px; color:var(--muted); font-weight:700; font-size:12.5px;}
.legal-grid{display:grid; grid-template-columns:220px 1fr; gap:28px; align-items:start;}
.legal-side{position:sticky; top:88px;}
.legal-side nav{display:flex; flex-direction:column; gap:6px; background:#fff; border:1px solid var(--line); border-radius:16px; padding:10px; box-shadow:var(--shadow-sm);}
.legal-side a{display:block; padding:10px 12px; border-radius:10px; color:var(--navy-800); font-weight:700; font-size:14px; text-decoration:none;}
.legal-side a:hover{background:rgba(11,42,91,.05);}
.legal-side a.is-on{background:linear-gradient(135deg,var(--navy-700),var(--navy-900)); color:#fff;}
.legal-body{background:#fff; border:1px solid var(--line); border-radius:20px; padding:28px 30px; box-shadow:var(--shadow-sm);}
.legal-body h3{margin:26px 0 8px; font-size:18px; font-weight:800; color:var(--navy-900);}
.legal-body h3:first-child{margin-top:0;}
.legal-body p, .legal-body li{color:var(--navy-800); font-size:15px; line-height:1.7; font-weight:500;}
.legal-body p{margin:0 0 12px;}
.legal-body ul{margin:0 0 12px; padding-left:20px;}
.legal-body li{margin:4px 0;}
.legal-body a{color:var(--gold-600); font-weight:700;}
.legal-cards{display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin:8px 0 18px;}
.legal-card{border:1px solid var(--line); border-radius:14px; padding:14px 16px; background:#f7f9fd;}
.legal-card b{display:block; color:var(--navy-900); font-size:14.5px; margin-bottom:4px;}
.legal-card span, .legal-card p{margin:0; color:var(--muted); font-size:13.5px; font-weight:600;}
@media (max-width:820px){
  .legal-grid{grid-template-columns:1fr;}
  .legal-side{position:static;}
  .legal-side nav{flex-direction:row; flex-wrap:wrap;}
  .legal-cards{grid-template-columns:1fr;}
  .legal-body{padding:20px 16px; border-radius:16px;}
}
</style>
@endpush
