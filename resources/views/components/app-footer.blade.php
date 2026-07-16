@php
    $fresh = $lastUpdated && now()->subHours(2) < $lastUpdated;
    $stale = $lastUpdated && now()->subHours(6) >= $lastUpdated;
@endphp

<footer class="shrink-0 h-[34px] bg-surface border-t border-line flex items-center px-4 md:px-5 gap-2 font-mono text-[11.5px] text-faint">
    <span @class([
        'w-[7px] h-[7px] rounded-full shrink-0',
        'bg-accent' => $fresh,
        'bg-amber-500' => ! $fresh && ! $stale,
        'bg-danger' => $stale,
    ])></span>

    @if ($lastUpdated)
        <span class="truncate">
            Dernière mise à jour le <b class="text-ink">{{ $lastUpdated->format('d/m/Y') }} à {{ $lastUpdated->format('H:i') }}</b>
            <span class="hidden sm:inline">· données RTE éCO2mix &amp; Actual Generation</span>
        </span>
    @else
        <span>Données RTE éCO2mix &amp; Actual Generation</span>
    @endif

    <span class="flex-1"></span>

    <a href="{{ route('welcome') }}" wire:navigate class="hover:text-ink hidden sm:inline">À propos</a>
    <span class="hidden sm:inline text-line-strong">·</span>
    <a href="https://github.com/production-nucleaire/app" target="_blank" rel="noopener noreferrer" class="hover:text-ink">GitHub ↗</a>
</footer>
