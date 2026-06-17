<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://images.pexels.com" crossorigin>
        <link rel="dns-prefetch" href="https://images.pexels.com">
        @if (! empty($lcpPreloadOrigin) && $lcpPreloadOrigin !== 'https://images.pexels.com')
            <link rel="preconnect" href="{{ $lcpPreloadOrigin }}" crossorigin>
        @endif
        @if (! empty($lcpPreloadImage))
            <link rel="preload" as="image" href="{{ $lcpPreloadImage }}" fetchpriority="high">
        @endif

        @vite('resources/js/app.js')
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
