@php
    use App\Support\LoadColor;
    // Distinct per-tranche tints (chart lines + unit-card top borders), cycled by position.
    $tints = ['#124a63', '#0d8a4f', '#5fb98a', '#7fd4a8', '#9aa4a9', '#2f89b0'];
@endphp

<div class="w-full h-full flex flex-col md:flex-row min-h-0 bg-panel">
    @if ($selectedPlant)
        @php
            $reactors = $selectedPlant->reactors->sortBy('reactor_index')->values();
            $first = $reactors->first();
            $commissionYear = $selectedPlant->reactors
                ->pluck('grid_link_date')->filter()->min()?->format('Y');
        @endphp

        <aside class="w-full md:w-[300px] shrink-0 bg-surface md:border-r border-line flex flex-col min-h-0 max-h-[38%] md:max-h-none">
            <div class="px-4 pt-3 pb-2 font-sans font-semibold text-[12px] tracking-[0.1em] text-faint shrink-0">
                {{ $this->plants->count() }} CENTRALES
            </div>
            <div class="flex-1 overflow-auto px-2.5 pb-3 flex flex-col gap-1">
                @foreach ($this->plants->sortByDesc->latest_production_mw as $plant)
                    @php
                        $isCurrent = $plant->id === $selectedPlant->id;
                        $color = LoadColor::var($plant->latest_production_mw, round($plant->percent_value));
                    @endphp
                    <a href="{{ route('plant', ['slug' => $plant->slug]) }}" wire:navigate wire:key="rail-{{ $plant->id }}"
                        @class([
                            'flex items-center gap-2.5 px-2.5 py-1.5 rounded-[9px] transition-colors',
                            'bg-brand-soft' => $isCurrent,
                            'hover:bg-panel' => ! $isCurrent,
                        ])>
                        <div class="w-6 h-6 rounded-full shrink-0 grid place-items-center text-white font-mono font-semibold text-[9.5px]" style="background:{{ $color }}">{{ $plant->active_reactors_count }}</div>
                        <div class="flex-1 min-w-0 flex justify-between items-baseline gap-1">
                            <span class="font-sans font-semibold text-[13px] text-ink truncate">{{ $plant->name }}</span>
                            <span class="font-mono font-medium text-[11.5px] text-muted shrink-0">{{ Number::format($plant->latest_production_mw, locale: 'fr') }}&nbsp;MW</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </aside>

        <section class="flex-1 min-w-0 min-h-0 overflow-auto">
            <div class="px-5 md:px-6 pt-5 flex flex-col md:flex-row gap-4 md:gap-5 md:items-center">
                <img class="w-full md:w-[190px] h-[106px] object-cover rounded-[10px] shrink-0"
                    src="{{ Vite::asset('resources/images/centrale-' . str($selectedPlant->name)->lower() . '.jpg') }}" alt="{{ $selectedPlant->name }}">
                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline gap-3 flex-wrap">
                        <h1 class="font-sans font-bold text-[26px] text-ink leading-tight">{{ $selectedPlant->name }}</h1>
                        <div class="font-mono font-semibold text-[16px] text-accent">
                            {{ Number::format($selectedPlant->latest_production_mw, locale: 'fr') }} MW · {{ round($selectedPlant->percent_value) }} %
                        </div>
                    </div>
                    <div class="flex gap-2 mt-2.5 flex-wrap font-mono text-[11.5px]">
                        @if ($selectedPlant->cooling_place)
                            <span class="text-brand bg-brand-soft rounded-md px-2.5 py-1">≋ {{ $selectedPlant->cooling_place }}</span>
                        @endif
                        @if ($first)
                            <span class="text-muted bg-panel rounded-md px-2.5 py-1">{{ $reactors->count() }} × {{ Number::format($first->net_power_mw, locale: 'fr') }} MW · {{ $first->stage }}</span>
                        @endif
                        @if ($commissionYear)
                            <span class="text-muted bg-panel rounded-md px-2.5 py-1">mise en service {{ $commissionYear }}</span>
                        @endif
                        @if ($selectedPlant->edf_link)
                            <a href="{{ $selectedPlant->edf_link }}" target="_blank" rel="noopener noreferrer" class="text-muted bg-panel rounded-md px-2.5 py-1 hover:text-ink">EDF ↗</a>
                        @endif
                        @if ($selectedPlant->wiki_link)
                            <a href="{{ $selectedPlant->wiki_link }}" target="_blank" rel="noopener noreferrer" class="text-muted bg-panel rounded-md px-2.5 py-1 hover:text-ink">Wikipédia ↗</a>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2 shrink-0 font-sans font-medium text-[12.5px]">
                    @if ($previousPlant)
                        <a href="{{ route('plant', ['slug' => $previousPlant->slug]) }}" wire:navigate class="border border-line-strong rounded-lg px-3 py-1.5 bg-surface text-muted hover:text-ink">← {{ $previousPlant->name }}</a>
                    @endif
                    @if ($nextPlant)
                        <a href="{{ route('plant', ['slug' => $nextPlant->slug]) }}" wire:navigate class="border border-line-strong rounded-lg px-3 py-1.5 bg-surface text-muted hover:text-ink">{{ $nextPlant->name }} →</a>
                    @endif
                </div>
            </div>

            <div class="px-5 md:px-6 pt-4 grid grid-cols-2 lg:grid-cols-4 gap-2.5">
                @foreach ($reactors as $i => $reactor)
                    @php
                        $value = $reactor->latestRecord?->value ?? 0;
                        $pct = $reactor->latestRecord?->percent_value ?? 0;
                        $color = LoadColor::var($value, $pct);
                        $status = match (true) {
                            $value < 0 => 'consommatrice',
                            $pct <= 1 => 'à l\'arrêt · maintenance',
                            $pct >= 98 => 'couplée · pleine puissance',
                            $pct >= 90 => 'couplée',
                            default => 'couplée · suivi de charge',
                        };
                    @endphp
                    <div class="border border-line rounded-[11px] px-3.5 py-3 bg-surface" style="border-top:3px solid {{ $tints[$i % count($tints)] }}" wire:key="unit-{{ $reactor->id }}">
                        <div class="flex justify-between items-baseline">
                            <span class="font-sans font-semibold text-[13px] text-ink">Tranche {{ $reactor->reactor_index }}</span>
                            <span class="font-mono text-[10.5px] text-faint">{{ Number::format($reactor->net_power_mw, locale: 'fr') }} MW</span>
                        </div>
                        <div class="font-mono font-semibold text-[22px] mt-1.5" style="color:{{ $color }}">{{ Number::format($value, locale: 'fr') }}</div>
                        <div class="flex items-center gap-2 mt-1.5">
                            <div class="flex-1 h-1 rounded-sm bg-track overflow-hidden">
                                <div class="h-full rounded-sm" style="background:{{ $color }}; width:{{ max($pct, 1) }}%"></div>
                            </div>
                            <span class="font-mono text-[10.5px] text-faint">{{ $pct }} %</span>
                        </div>
                        <div class="font-mono text-[10.5px] text-faint/80 mt-1.5">{{ $status }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mx-5 md:mx-6 my-4 bg-surface border border-line rounded-[12px] px-4 pt-3.5 pb-2"
                x-data="{ win: 48, hidden: [] }">
                <div class="flex items-center gap-4 pb-2.5 flex-wrap">
                    <div class="font-sans font-semibold text-[14px] text-ink">Production horaire par tranche</div>
                    <div class="flex gap-3 font-mono text-[11px] text-muted flex-wrap">
                        @foreach ($reactors as $i => $reactor)
                            <button type="button"
                                x-on:click="hidden.includes({{ $i }}) ? hidden = hidden.filter(n => n !== {{ $i }}) : hidden.push({{ $i }}); window.reactorToggle && window.reactorToggle({{ $i }})"
                                class="flex items-center gap-1.5 transition-opacity"
                                x-bind:class="hidden.includes({{ $i }}) ? 'opacity-30 line-through' : ''"
                                title="Afficher / masquer">
                                <span class="w-2.5 h-2.5 rounded-full" style="background:{{ $tints[$i % count($tints)] }}"></span>T{{ $reactor->reactor_index }}
                            </button>
                        @endforeach
                    </div>
                    <div class="flex-1"></div>
                    <div class="font-mono text-[11px] text-faint hidden md:block">⟷ glisser pour remonter le temps</div>
                    <div class="flex gap-0.5 border border-line rounded-lg p-0.5 font-mono text-[11.5px]">
                        @foreach (['24 h' => 24, '48 h' => 48, '7 j' => 168, '30 j' => 720] as $label => $hours)
                            <button type="button"
                                x-on:click="win = {{ $hours }}; window.setReactorWindow && window.setReactorWindow({{ $hours }})"
                                class="px-2.5 py-[3px] rounded-md"
                                x-bind:class="win === {{ $hours }} ? 'bg-brand text-white' : 'text-faint hover:text-ink'">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div wire:ignore x-ref="chart" id="reactor-chart" class="w-full" style="height:320px"></div>
            </div>
        </section>
    @else
        <aside class="w-full md:w-[340px] shrink-0 bg-surface md:border-r border-line flex flex-col min-h-0 max-h-[45%] md:max-h-none">
            @if ($this->previewPlant)
                @php
                    $pp = $this->previewPlant;
                    $ppReactors = $pp->reactors->sortBy('reactor_index');
                @endphp
                <div wire:key="preview-{{ $pp->id }}" wire:transition.opacity class="flex flex-col h-full min-h-0">
                    <div class="flex items-center justify-between px-4 pt-3 pb-2 shrink-0">
                        <button type="button" wire:click="$set('previewPlantId', 0)" class="font-sans text-[12.5px] text-muted hover:text-ink">‹ retour</button>
                        <a href="{{ route('plant', ['slug' => $pp->slug]) }}" wire:navigate class="font-sans font-medium text-[12.5px] text-brand hover:underline">Voir la centrale →</a>
                    </div>
                    <div class="flex-1 overflow-auto px-4 pb-4 flex flex-col gap-3">
                        <div class="relative rounded-[10px] overflow-hidden shrink-0">
                            <img class="w-full h-32 object-cover" src="{{ Vite::asset('resources/images/centrale-' . str($pp->name)->lower() . '.jpg') }}" alt="{{ $pp->name }}">
                            <span class="absolute bottom-0 right-0 bg-black/60 text-white text-[10px] font-mono rounded-tl-lg px-2 py-0.5">© EDF</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-2">
                            <h2 class="font-sans font-bold text-[20px] text-ink leading-tight">{{ $pp->name }}</h2>
                            <span class="font-mono font-semibold text-[13px] text-accent shrink-0">{{ Number::format($pp->latest_production_mw, locale: 'fr') }} MW · {{ round($pp->percent_value) }} %</span>
                        </div>
                        <div class="flex gap-1.5 flex-wrap font-mono text-[10.5px]">
                            @if ($pp->cooling_place)
                                <span class="text-brand bg-brand-soft rounded-md px-2 py-0.5">≋ {{ $pp->cooling_place }}</span>
                            @endif
                            <span class="text-muted bg-panel rounded-md px-2 py-0.5">{{ $ppReactors->count() }} tranches</span>
                            @if ($pp->edf_link)
                                <a href="{{ $pp->edf_link }}" target="_blank" rel="noopener noreferrer" class="text-muted bg-panel rounded-md px-2 py-0.5 hover:text-ink">EDF ↗</a>
                            @endif
                            @if ($pp->wiki_link)
                                <a href="{{ $pp->wiki_link }}" target="_blank" rel="noopener noreferrer" class="text-muted bg-panel rounded-md px-2 py-0.5 hover:text-ink">Wikipédia ↗</a>
                            @endif
                        </div>
                        <div class="font-sans font-semibold text-[11px] tracking-[0.1em] text-faint pt-1">PRODUCTION PAR TRANCHE</div>
                        <div class="flex flex-col gap-2">
                            @foreach ($ppReactors as $reactor)
                                @php
                                    $rv = $reactor->latestRecord?->value ?? 0;
                                    $rpct = $reactor->latestRecord?->percent_value ?? 0;
                                    $rcolor = \App\Support\LoadColor::var($rv, $rpct);
                                @endphp
                                <div wire:key="pp-{{ $reactor->id }}" class="flex items-center gap-2.5">
                                    <span class="font-sans font-semibold text-[12.5px] text-ink w-[70px] shrink-0">Tranche {{ $reactor->reactor_index }}</span>
                                    <div class="flex-1 h-1.5 rounded-sm bg-track overflow-hidden">
                                        <div class="h-full rounded-sm" style="background:{{ $rcolor }}; width:{{ max($rpct, 1) }}%"></div>
                                    </div>
                                    <span class="font-mono text-[11px] text-muted w-[64px] text-right shrink-0">{{ Number::format($rv, locale: 'fr') }} MW</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="font-sans font-semibold text-[11px] tracking-[0.1em] text-faint pt-1">DERNIÈRES 24 H</div>
                        <div class="[&_svg]:h-[60px]">{!! $this->previewSpark() !!}</div>
                    </div>
                </div>
            @else
                <div wire:key="plant-list" wire:transition.opacity class="h-full min-h-0 flex flex-col">
                    <x-plant-list :plants="$this->plants" />
                </div>
            @endif
        </aside>

        <section class="flex-1 relative min-w-0 min-h-0">
            <div wire:ignore id="map" class="absolute inset-0 bg-panel"></div>

            <div class="absolute left-3.5 bottom-3.5 z-[500] bg-surface/95 border border-line rounded-[10px] px-3.5 py-2.5 flex gap-4 font-mono text-[11.5px] text-muted backdrop-blur-sm">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full border-[2.5px]" style="border-color:var(--color-accent)"></span>charge (%)</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full border-[2.5px]" style="border-color:var(--color-idle)"></span>à l'arrêt</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full border-[2.5px]" style="border-color:var(--color-danger)"></span>consommatrice</span>
            </div>
        </section>
    @endif
</div>

@php
    $boot = [
        'markers' => $this->markers,
        'reactor' => $selectedPlant ? [
            'series' => $this->reactorSeries(),
            'slug' => $selectedPlant->slug,
            'tints' => array_values(array_slice($tints, 0, $selectedPlant->reactors->count())),
            'minTime' => $this->reactorMinTime(),
            'maxMw' => (int) $selectedPlant->reactors->sum('net_power_mw'),
        ] : null,
    ];
@endphp
@script
<script>
    const boot = @js($boot);

    if (document.getElementById('map')) {
        createPlantMap(boot.markers);
    }

    const reactorEl = document.getElementById('reactor-chart');
    if (boot.reactor && reactorEl && window.createReactorChart) {
        window.reactorChart = createReactorChart(reactorEl, boot.reactor.series, {
            slug: boot.reactor.slug,
            tints: boot.reactor.tints,
            minTime: boot.reactor.minTime,
            maxMw: boot.reactor.maxMw,
        });
    }
</script>
@endscript
