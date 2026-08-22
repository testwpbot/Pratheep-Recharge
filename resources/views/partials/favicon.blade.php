@php
  $seoFav = \App\Models\Setting::seo();
  $favPath = $seoFav['favicon_path'] ?? '';
  $favFile = ($favPath !== '' && is_file(public_path($favPath))) ? public_path($favPath) : public_path('assets/logo-mark.png');
  $favHref = ($favPath !== '' && is_file(public_path($favPath))) ? asset($favPath) : asset('assets/logo-mark.png');
  $favExt = strtolower(pathinfo($favFile, PATHINFO_EXTENSION));
  $favType = match ($favExt) {
      'ico' => 'image/x-icon',
      'svg' => 'image/svg+xml',
      'webp' => 'image/webp',
      'gif' => 'image/gif',
      'jpg', 'jpeg' => 'image/jpeg',
      default => 'image/png',
  };
  $favVer = @filemtime($favFile) ?: time();
@endphp
<link rel="icon" type="{{ $favType }}" href="{{ $favHref }}?v={{ $favVer }}">
<link rel="apple-touch-icon" href="{{ $favHref }}?v={{ $favVer }}">
