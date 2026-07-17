@php
    // Shared grid template so the header row and every data row line up.
    $grid = 'display:grid; grid-template-columns:minmax(200px,230px) 78px 92px 108px 66px minmax(90px,1fr) 168px 108px; gap:0 14px; align-items:center;';
    $counts = $this->counts();
    $filters = [
        'toutes' => 'Toutes · '.$counts['toutes'],
        'couplees' => 'Couplées · '.$counts['couplees'],
        'arret' => 'À l\'arrêt · '.$counts['arret'],
    ];
@endphp

<div class="w-full h-full flex flex-col min-h-0 bg-panel">
    {{-- Toolbar --}}
    <div class="shrink-0 flex items-center gap-2.5 flex-wrap px-4 md:px-5 py-2.5 bg-surface border-b border-line">
        {{-- Carte | Tableau --}}
        <div class="flex gap-0.5 border border-line rounded-lg p-0.5 font-sans text-[12px]">
            <a href="{{ route('home') }}" wire:navigate class="px-3 py-1 rounded-md text-faint hover:text-ink transition-colors">Carte</a>
            <span class="px-3 py-1 rounded-md bg-brand text-white font-semibold">Tableau</span>
        </div>

        {{-- Search --}}
        <div class="flex items-center gap-2 border border-line rounded-lg px-3 py-1.5 w-full sm:w-[240px] text-muted font-sans text-[12.5px]">
            <span class="text-faint">⌕</span>
            <input type="text" wire:model.live.debounce.250ms="search" placeholder="Rechercher une centrale…"
                class="bg-transparent outline-none w-full text-ink placeholder:text-faint">
        </div>

        {{-- Filter chips --}}
        <div class="flex gap-1.5 flex-wrap">
            @foreach ($filters as $key => $label)
                <button type="button" wire:click="setFilter(@js($key))" wire:key="filter-{{ $key }}"
                    @class([
                        'px-3 py-[5px] rounded-[14px] font-mono text-[11.5px] border transition-colors',
                        'bg-brand-soft text-brand border-brand/40' => $filter === $key,
                        'bg-surface text-faint border-line hover:text-ink' => $filter !== $key,
                    ])>{{ $label }}</button>
            @endforeach
        </div>

        <div class="flex-1"></div>

        {{-- Sort --}}
        <label class="flex items-center gap-1.5 font-mono text-[11px] text-faint">
            tri :
            <select wire:model.live="sort"
                class="bg-surface border border-line rounded-lg px-2 py-1 text-brand font-medium outline-none [color-scheme:light] dark:[color-scheme:dark]">
                <option value="production">production</option>
                <option value="charge">charge</option>
                <option value="nom">nom</option>
            </select>
        </label>

        {{-- Export --}}
        <button type="button" wire:click="exportCsv"
            class="border border-line-strong rounded-lg px-3 py-1.5 font-sans font-medium text-[12px] text-brand bg-surface hover:border-brand/50 transition-colors">
            ↓ Exporter CSV
        </button>
    </div>

    {{-- Table --}}
    <div class="flex-1 overflow-auto">
        <div class="min-w-[940px]">
            {{-- Column header --}}
            <div class="sticky top-0 z-10 bg-panel border-b border-line px-4 md:px-5 py-2.5 font-sans font-semibold text-[10.5px] tracking-[0.07em] text-faint"
                style="{{ $grid }}">
                <span>CENTRALE / TRANCHE</span>
                <span>PALIER</span>
                <span class="text-right">PN (MW)</span>
                <span class="text-right">PRODUCTION</span>
                <span class="text-right">CHARGE</span>
                <span></span>
                <span>STATUT</span>
                <span class="text-right">TENDANCE 24 H</span>
            </div>

            @forelse ($this->groups as $group)
                {{-- Group (plant) header --}}
                <div wire:key="group-{{ $group['slug'] }}">
                    <div class="bg-surface border-b border-line px-4 md:px-5 py-2" style="{{ $grid }}">
                        <span class="flex items-center gap-2">
                            <a href="{{ route('plant', ['slug' => $group['slug']]) }}" wire:navigate
                                class="font-sans font-bold text-[13px] text-ink hover:text-brand transition-colors">{{ $group['name'] }}</a>
                            <span class="font-mono text-[10px] text-white rounded-full px-2 py-0.5" style="background:{{ $group['barVar'] }}">{{ $group['online'] }}/{{ $group['total'] }}</span>
                        </span>
                        <span class="font-mono text-[11.5px] text-faint">{{ $group['palier'] }}</span>
                        <span class="font-mono text-[11.5px] text-faint text-right">{{ Number::format($group['pn'], locale: 'fr') }}</span>
                        <span class="font-mono font-semibold text-[12.5px] text-ink text-right">{{ Number::format($group['mw'], locale: 'fr') }}</span>
                        <span class="font-mono text-[11.5px] text-muted text-right">{{ $group['pct'] }} %</span>
                        <span class="h-[5px] rounded-sm bg-track overflow-hidden">
                            <span class="block h-full rounded-sm" style="background:{{ $group['barVar'] }}; width:{{ max($group['pct'], 1) }}%"></span>
                        </span>
                        <span></span>
                        <span></span>
                    </div>

                    {{-- Reactor rows --}}
                    @foreach ($group['units'] as $unit)
                        <div wire:key="unit-{{ $group['slug'] }}-{{ $unit['index'] }}"
                            class="border-b border-line/60 px-4 md:px-5 py-[7px] hover:bg-surface transition-colors" style="{{ $grid }}">
                            <span class="font-sans text-[12.5px] text-muted pl-6">{{ $unit['name'] }}</span>
                            <span class="font-mono text-[11.5px] text-faint">{{ $unit['palier'] }}</span>
                            <span class="font-mono text-[11.5px] text-faint text-right">{{ Number::format($unit['pn'], locale: 'fr') }}</span>
                            <span class="font-mono font-semibold text-[12.5px] text-right" style="color:{{ $unit['on'] || $unit['value'] < 0 ? 'var(--color-ink)' : 'var(--color-faint)' }}">{{ Number::format($unit['value'], locale: 'fr') }}</span>
                            <span class="font-mono text-[11.5px] text-muted text-right">{{ $unit['pct'] }} %</span>
                            <span class="h-1 rounded-sm bg-track overflow-hidden">
                                <span class="block h-full rounded-sm" style="background:{{ $unit['colorVar'] }}; width:{{ max($unit['pct'], 1) }}%"></span>
                            </span>
                            <span class="font-mono text-[11px] flex items-center gap-1.5" style="color:{{ $unit['statusVar'] }}">
                                <span class="w-[7px] h-[7px] rounded-full shrink-0" style="background:{{ $unit['statusVar'] }}"></span>{{ $unit['status'] }}
                            </span>
                            <span class="font-mono text-[11.5px] text-right" style="color:{{ $unit['trendVar'] }}">{{ $unit['trend'] }}</span>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="px-5 py-16 text-center font-sans text-[13px] text-faint">Aucune tranche ne correspond à ces critères.</div>
            @endforelse
        </div>
    </div>
</div>
