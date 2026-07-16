<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>L’Atome Français - Suivi de la production électro-nucléaire française heure par heure</title>

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
