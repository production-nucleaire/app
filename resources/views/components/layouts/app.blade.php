<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>L’Atome Français - Suivi de la production électro-nucléaire française heure par heure</title>

        {{-- Favicons (logo.svg for modern browsers, .ico + apple-touch fallbacks) --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        {{-- Open Graph / Twitter share card (image rendered by ShareImageService) --}}
        @php
            $og = \App\Support\OgImage::forCurrentRoute();
        @endphp
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="électronucléaire.fr">
        <meta property="og:locale" content="fr_FR">
        <meta property="og:url" content="{{ $og['url'] }}">
        <meta property="og:title" content="{{ $og['title'] }}">
        <meta property="og:description" content="{{ $og['description'] }}">
        <meta property="og:image" content="{{ $og['image'] }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $og['title'] }}">
        <meta name="twitter:description" content="{{ $og['description'] }}">
        <meta name="twitter:image" content="{{ $og['image'] }}">

        <script>
            (() => {
                const dark = localStorage.theme === 'dark'
                    || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        @livewireStyles
        @livewireScripts

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-screen flex flex-col bg-body font-sans text-ink antialiased overflow-hidden">
        @php
            $active = match (true) {
                request()->routeIs('history') => 'history',
                request()->routeIs('plant'), request()->routeIs('reactor') => 'plant',
                request()->routeIs('table') => 'national',
                default => 'national',
            };
        @endphp

        <x-app-header :active="$active" />

        <main class="flex-1 min-h-0">
            {{ $slot }}
        </main>

        <x-app-footer />
    </body>
</html>
