<!DOCTYPE html>
<html lang="en" class="is-loading">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Sign In · Happy Pratheep Recharge')</title>
<meta name="description" content="Sign in or create an account on Happy Pratheep Recharge.">
<link rel="icon" type="image/png" href="{{ asset('assets/logo-mark.png') }}">
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
<link rel="stylesheet" href="{{ asset('css/loader.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body data-shell="auth">

@include('partials.loader')

@include('partials.topbar')
@include('partials.nav')

<main>
    @yield('content')
</main>

@include('partials.footer')

<script src="{{ asset('js/landing.js') }}"></script>
@stack('scripts')
</body>
</html>
