<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>

    <!-- CDN Libraries -->
    @include('layouts.cdn')

    <!-- Vite -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @stack('styles')
</head>
<body>
    <div class="bg-pattern"></div>
    <div id="auth-container">
        <div class="auth-animate auth-content-wrapper">
            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
