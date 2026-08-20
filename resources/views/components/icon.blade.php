@php
    $icons = [
        // --- topbar ---
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'mail'  => '<path d="M4 4h16v16H4z"/><path d="m4 6 8 6 8-6"/>',
        // --- nav ---
        'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'bolt-nav' => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'caret'    => '<path d="m7 10 5 5 5-5z"/>',
        // --- dropdown menus ---
        'phone-menu' => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/>',
        'bill'       => '<path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'wifi'       => '<path d="M5 12.55a11 11 0 0 1 14 0"/><path d="M8.5 16.1a6 6 0 0 1 7 0"/><path d="M2 8.8a16 16 0 0 1 20 0"/><path d="M12 20h.01"/>',
        'tv'         => '<rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10h.01M10 10h.01"/>',
        'upload'     => '<path d="M4 20h16"/><path d="M12 4v12"/><path d="m7 9 5-5 5 5"/>',
        'bolt'       => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'drop'       => '<path d="M12 2.7 6.8 9a7 7 0 1 0 10.4 0z"/>',
        'plus'       => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8M12 8v8"/>',
        // --- drawer ---
        'home'       => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V21h13V9.5"/><path d="M10 21v-6h4v6"/>',
        'mobile-dr'  => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M10.5 18.5h3"/><path d="M12 6.5v5"/><path d="m9.8 8.7 2.2-2.2 2.2 2.2"/>',
        'postpaid-dr'=> '<path d="M6 2.5h12v19l-2-1.4-2 1.4-2-1.4-2 1.4-2-1.4-2 1.4z"/><path d="M9.5 8h5M9.5 12h5"/>',
        'wifi-dr'    => '<path d="M5 12.5a11 11 0 0 1 14 0"/><path d="M8.5 16a6 6 0 0 1 7 0"/><path d="M2 9a16 16 0 0 1 20 0"/><path d="M12 19.5h.01"/>',
        'router-dr'  => '<rect x="2.5" y="13" width="19" height="7.5" rx="2"/><path d="M6.5 16.8h.01M10 16.8h.01"/><path d="M12 13V9"/><path d="M8.5 6.5a5 5 0 0 1 7 0"/><path d="M6 4a8.5 8.5 0 0 1 12 0"/>',
        'bolt-dr'    => '<path d="M13 2.5 5 13.5h6l-1 8 8-11h-6z"/>',
        'drop-dr'    => '<path d="M12 3.2 7 9.3a6.4 6.4 0 1 0 10 0z"/>',
        'gift-dr'    => '<rect x="3" y="8.5" width="18" height="5" rx="1.2"/><path d="M4.5 13.5V21h15v-7.5"/><path d="M12 8.5V21"/><path d="M12 8.5S10.6 4 8.4 4a2.2 2.2 0 0 0 0 4.5z"/><path d="M12 8.5S13.4 4 15.6 4a2.2 2.2 0 0 1 0 4.5z"/>',
        'headset-dr' => '<path d="M4 13.5v-1.7a8 8 0 0 1 16 0v1.7"/><rect x="2.6" y="13" width="4.2" height="6" rx="1.6"/><rect x="17.2" y="13" width="4.2" height="6" rx="1.6"/><path d="M20 19v.6a2.6 2.6 0 0 1-2.6 2.6H13"/>',
        // --- service cards ---
        'mobile'   => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M10.5 18.5h3"/><path d="M12 6.5v5"/><path d="m9.8 8.7 2.2-2.2 2.2 2.2"/>',
        'router'   => '<rect x="2.5" y="13" width="19" height="7.5" rx="2"/><path d="M6.5 16.8h.01M10 16.8h.01"/><path d="M12 13V9"/><path d="M8.5 6.5a5 5 0 0 1 7 0"/><path d="M6 4a8.5 8.5 0 0 1 12 0"/>',
        'tv-card'  => '<rect x="2.5" y="6" width="19" height="13" rx="2.2"/><path d="M8.5 22h7"/><path d="m8 2.8 4 3.2 4-3.2"/>',
        'gift'      => '<rect x="3" y="8.5" width="18" height="5" rx="1.2"/><path d="M4.5 13.5V21h15v-7.5"/><path d="M12 8.5V21"/><path d="M12 8.5S10.6 4 8.4 4a2.2 2.2 0 0 0 0 4.5z"/><path d="M12 8.5S13.4 4 15.6 4a2.2 2.2 0 0 1 0 4.5z"/>',
        // --- service-card icons ---
        'phone-menu' => '<rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M10.5 18.5h3"/><path d="M12 6.5v5"/><path d="m9.8 8.7 2.2-2.2 2.2 2.2"/>',
        'shield' => '<path d="M12 2.7 4.5 6v6c0 4.6 3.2 8.2 7.5 9.3 4.3-1.1 7.5-4.7 7.5-9.3V6z"/>',
        // --- steps ---
        'grid'  => '<rect x="3" y="3" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="2"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="2"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2"/>',
        'form'  => '<rect x="4" y="2.5" width="16" height="19" rx="2.5"/><path d="M8.5 7h7M8.5 11h7M8.5 15h4"/>',
        'check' => '<circle cx="12" cy="12" r="9.2"/><path d="m8.2 12.3 2.6 2.6 5-5.4"/>',
        // --- why us ---
        'clock'   => '<circle cx="12" cy="12" r="9.2"/><path d="M12 6.8V12l3.4 2"/>',
        'tag'     => '<path d="M11.6 2.9H20a1 1 0 0 1 1 1v8.4a2 2 0 0 1-.6 1.4l-6.7 6.7a2 2 0 0 1-2.8 0l-7-7a2 2 0 0 1 0-2.8l6.3-6.3a2 2 0 0 1 1.4-.4z"/><circle cx="16.6" cy="7.4" r="1.5"/>',
        'users'   => '<circle cx="12" cy="5" r="2.6"/><circle cx="5" cy="18" r="2.6"/><circle cx="19" cy="18" r="2.6"/><path d="M10.2 7 6.4 15.6M13.8 7l3.8 8.6M7.6 18h8.8"/>',
        'shield'  => '<path d="M12 2.7 4.5 6v6c0 4.6 3.2 8.2 7.5 9.3 4.3-1.1 7.5-4.7 7.5-9.3V6z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/>',
        'headset' => '<path d="M4 13.5v-1.7a8 8 0 0 1 16 0v1.7"/><rect x="2.6" y="13" width="4.2" height="6" rx="1.6"/><rect x="17.2" y="13" width="4.2" height="6" rx="1.6"/><path d="M20 19v.6a2.6 2.6 0 0 1-2.6 2.6H13"/>',
        // --- footer contact ---
        'pin'    => '<path d="M12 21.5s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11z"/><circle cx="12" cy="10.3" r="2.8"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'x'      => '<path d="M6 6l12 12M18 6 6 18"/>',
        // --- hero bolt ---
        'bolt-hero' => '<circle opacity=".5" cx="25" cy="25" r="23" fill="url(#hpr-bolt)"/><path d="M28.6 5.4a1.1 1.1 0 0 1 1.9 1l-2.7 12.2h7.4a1.1 1.1 0 0 1 .85 1.82L22.4 44.7a1.1 1.1 0 0 1-1.93-1l2.7-12.2h-7.4a1.1 1.1 0 0 1-.86-1.8z" fill="#fff"/>',
        // --- admin ---
        'wallet' => '<rect x="2.5" y="6.5" width="19" height="13" rx="2.2"/><path d="M16 13h5.5v4"/><circle cx="15" cy="13" r="1.6" fill="currentColor" stroke="none"/><path d="M2.5 10.5h19"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'trash' => '<path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
        'ban' => '<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>',
        // --- download ---
        'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'alert' => '<path d="M10.3 3.9 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'send' => '<path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
    ];

    $name = $name ?? 'bolt';
    $class = $class ?? '';
    $size  = $size ?? 24;
    $viewBox = $name === 'bolt-hero' ? '0 0 50 50' : '0 0 24 24';
    $strokeW = $stroke ?? ($name === 'bolt-hero' ? 0 : 2);
@endphp
<svg class="{{ $class }}" width="{{ $size }}" height="{{ $size }}" viewBox="{{ $viewBox }}"
     @if($name !== 'bolt-hero') fill="none" stroke="currentColor" stroke-width="{{ $strokeW }}" stroke-linecap="round" stroke-linejoin="round" @endif
     xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    @if($name === 'bolt-hero')
        <defs>
            <linearGradient id="hpr-bolt" x1="25" y1="2" x2="25" y2="48" gradientUnits="userSpaceOnUse">
                <stop stop-color="#fff" stop-opacity=".71"/>
                <stop offset="1" stop-color="#fff" stop-opacity="0"/>
            </linearGradient>
        </defs>
    @endif
    {!! $icons[$name] ?? $icons['bolt'] !!}
</svg>
