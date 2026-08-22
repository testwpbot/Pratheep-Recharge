<!DOCTYPE html>
<html lang="en" class="is-loading">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@include('partials.seo')
@include('partials.favicon')
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
@include('partials.whatsapp-float')

<script src="{{ asset('js/landing.js') }}"></script>
@stack('scripts')
</body>
</html>
