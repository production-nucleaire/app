@php
    $gw = number_format($stats['injected_gw'], 1, ',', "\u{202f}");
    $plantHref = $stats['top_plant_slug'] ? route('plant', $stats['top_plant_slug']) : route('home');
@endphp

<header class="shrink-0 h-16 bg-surface border-b border-line flex items-center gap-4 md:gap-5 px-4 md:px-5">
    <a href="{{ route('home') }}" wire:navigate class="js-logo flex items-center gap-2.5 shrink-0">
        <span class="js-fizz w-6 h-6 rounded-[7px] bg-brand grid place-items-center cursor-pointer" title="⚛">
            <span class="w-2 h-2 rounded-full bg-accent-light"></span>
        </span>
        <span class="font-sans font-bold text-[15px] text-ink hidden sm:inline">
            électronucléaire<span class="text-faint">.fr</span>
        </span>
    </a>

    <nav class="flex gap-1 bg-panel rounded-lg p-[3px] text-[12.5px] font-medium">
        @php
            $tabBase = 'px-3 md:px-3.5 py-[5px] rounded-[7px] transition-colors';
            $tabActive = 'bg-surface text-ink shadow-sm';
            $tabIdle = 'text-muted hover:text-ink';
        @endphp
        <a href="{{ route('home') }}" wire:navigate class="{{ $tabBase }} {{ $active === 'national' ? $tabActive : $tabIdle }}">National</a>
        <a href="{{ $plantHref }}" wire:navigate class="{{ $tabBase }} {{ $active === 'plant' ? $tabActive : $tabIdle }}">Par centrale</a>
        <a href="{{ route('history') }}" wire:navigate class="{{ $tabBase }} {{ $active === 'history' ? $tabActive : $tabIdle }}">Historique</a>
    </nav>

    <div class="flex-1"></div>

    <div class="hidden lg:flex items-center gap-4 font-mono text-[12.5px] text-muted">
        <span><b class="text-ink text-[15px]">{{ $gw }} GW</b> injectés</span>
        <span class="w-px h-[18px] bg-line"></span>
        <span><b class="text-ink text-[15px]">{{ $stats['coupled'] }}/{{ $stats['total_reactors'] }}</b> tranches</span>
        <span class="w-px h-[18px] bg-line"></span>
        <span><b class="text-accent text-[15px]">{{ $stats['load_factor_pct'] }} %</b> du parc</span>
    </div>

    <div class="w-[150px] shrink-0 hidden xl:block [&_svg]:h-[34px]">{!! $spark !!}</div>

    <div
        x-data="{ t: '' }"
        x-init="const u = () => t = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', timeZone: 'Europe/Paris' }); u(); setInterval(u, 1000)"
        class="font-mono text-[11px] text-faint whitespace-nowrap flex items-center gap-1.5"
    >
        <span class="w-[7px] h-[7px] rounded-full bg-accent animate-pulse-dot"></span>
        <span x-text="t"></span>
    </div>

    <div x-data="{ open: false }" x-on:click.away="open = false" class="relative shrink-0">
        <button
            type="button"
            x-on:click="open = !open"
            class="w-8 h-8 border border-line-strong rounded-lg grid place-items-center text-muted hover:text-ink text-sm"
            title="Thème"
        >
            ◐
        </button>
        <div x-show="open" x-cloak
            class="absolute right-0 top-10 z-[1200] flex flex-col min-w-32 bg-surface border border-line rounded-lg shadow-lg p-1 text-[12.5px] text-ink">
            <button
                type="button" 
                class="text-left px-3 py-1.5 rounded-md hover:bg-panel"
                x-on:click="localStorage.setItem('theme','dark'); document.documentElement.classList.add('dark'); open = false"
            >
                    Sombre
            </button>
            <button
                type="button" 
                class="text-left px-3 py-1.5 rounded-md hover:bg-panel"
                x-on:click="localStorage.setItem('theme','light'); document.documentElement.classList.remove('dark'); open = false"
            >
                    Clair
            </button>
            <button
                type="button" 
                class="text-left px-3 py-1.5 rounded-md hover:bg-panel"
                x-on:click="localStorage.removeItem('theme'); document.documentElement.classList.toggle('dark', window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); open = false"
            >
                    Système
            </button>
        </div>
    </div>
</header>
