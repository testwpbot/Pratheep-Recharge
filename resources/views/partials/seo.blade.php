@php
  $seo = \App\Models\Setting::seo();
  $siteName = \App\Models\Setting::get('general', 'site_name', config('app.name', 'Happy Pratheep Recharge'));
  $title = trim($__env->yieldContent('title'));
  $pageTitle = trim((string) ($seo['meta_title'] ?? ''));
  if ($title !== '') {
      $fullTitle = $title;
  } elseif ($pageTitle !== '') {
      $fullTitle = $pageTitle;
  } else {
      $fullTitle = $siteName . ' — Mobile reloads, bills and DTH in Sri Lanka';
  }
  $desc = trim($__env->yieldContent('meta_description'));
  if ($desc === '') {
      $desc = $seo['meta_description'] ?? 'Mobile reloads, data packages, ISP bills and utility payments for Sri Lanka. Fast, secure bank transfers.';
  }
  $keywords = $seo['meta_keywords'] ?? '';
  $ogTitle = trim((string) ($seo['og_title'] ?? '')) ?: $fullTitle;
  $ogDesc = trim((string) ($seo['og_description'] ?? '')) ?: $desc;
  $ogImage = trim((string) ($seo['og_image_url'] ?? ''));
  if ($ogImage === '' && ! empty($seo['og_image_path'])) {
      $ogImage = asset($seo['og_image_path']);
  }
  if ($ogImage === '') {
      $ogImage = asset('assets/logo.png');
  }
  $robots = ($seo['robots'] ?? 'index') === 'noindex' ? 'noindex,follow' : 'index,follow';
  $verify = $seo['google_site_verification'] ?? '';
@endphp
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $desc }}">
@if($keywords)<meta name="keywords" content="{{ $keywords }}">@endif
<meta name="robots" content="{{ $robots }}">
@if($verify)<meta name="google-site-verification" content="{{ $verify }}">@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDesc }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDesc }}">
<meta name="twitter:image" content="{{ $ogImage }}">
