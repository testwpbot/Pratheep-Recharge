@php
  $wa = \App\Models\Setting::whatsapp();
@endphp
@if(!empty($wa['href']))
  <a class="hpr-wa" href="{{ $wa['href'] }}" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp">
    <span class="hpr-wa__pulse" aria-hidden="true"></span>
    <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true">
      <path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2m5.1 14c-.2.6-1.2 1.2-1.7 1.2s-1.2.2-3.6-.9c-3-1.3-4.8-4.4-5-4.6s-1.2-1.6-1.2-3 .8-2.1 1-2.4a1 1 0 0 1 .8-.3h.6c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .6l-.4.5-.3.4c-.1.1-.3.3-.1.6a9 9 0 0 0 1.6 2 8 8 0 0 0 2.3 1.4c.3.2.5.1.6 0l1-1.2c.2-.2.4-.2.6-.1l2 1c.3.1.5.2.5.3z"/>
    </svg>
  </a>
  @once
  <style>
  .hpr-wa{
    position:fixed; right:18px; bottom:18px; z-index:1400;
    width:58px; height:58px; border-radius:50%;
    display:inline-flex; align-items:center; justify-content:center;
    background:#25d366; color:#fff;
    box-shadow:0 10px 24px rgba(18,140,70,.38);
    text-decoration:none;
    bottom:calc(18px + env(safe-area-inset-bottom));
    transition:transform .18s ease, box-shadow .18s ease;
  }
  .hpr-wa:hover{transform:translateY(-2px) scale(1.04); box-shadow:0 14px 28px rgba(18,140,70,.46); color:#fff;}
  .hpr-wa svg{display:block; position:relative; z-index:1;}
  .hpr-wa__pulse{
    position:absolute; inset:-5px; border-radius:50%;
    border:2px solid rgba(37,211,102,.55);
    animation:hprWaPulse 1.8s ease-out infinite;
    pointer-events:none;
  }
  @keyframes hprWaPulse{
    0%{transform:scale(.92); opacity:.8;}
    100%{transform:scale(1.28); opacity:0;}
  }
  html.hpr-pop-lock .hpr-wa{visibility:hidden; pointer-events:none;}
  @media (max-width:720px){
    .hpr-wa{width:54px; height:54px; right:14px;}
  }
  </style>
  @endonce
@endif
