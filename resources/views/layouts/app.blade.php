<!DOCTYPE html>
<html lang="en" class="is-loading">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Happy Pratheep Recharge — Mobile Reloads, ISP & Utility Bill Payments in Sri Lanka')</title>
<meta name="description" content="@yield('meta_description', 'Mobile reloads, data packages, ISP bill payments and utility bills for all Sri Lankan providers. Fast turnaround, secure bank transfers, 24/7 support.')">
<link rel="icon" type="image/png" href="{{ asset('assets/logo-mark.png') }}">
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
<link rel="stylesheet" href="{{ asset('css/loader.css') }}">
@stack('styles')
</head>
<body data-shell="landing">

@include('partials.loader')

@include('partials.topbar')
@include('partials.nav')

<main id="app">
    @yield('content')
</main>

@include('partials.footer')

<script src="{{ asset('js/landing.js') }}"></script>
@stack('scripts')
</body>
</html>
