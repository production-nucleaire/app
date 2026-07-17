@php
    $fr1 = fn ($v) => number_format($v, 1, ',', "\u{202f}");
    $fr2 = fn ($v) => number_format($v, 2, ',', "\u{202f}");
    $delta = function (?float $pct) {
        if ($pct === null) {
            return ['label' => '—', 'var' => 'var(--color-idle)'];
        }
        $dir = $pct >= 0 ? '▲ +' : '▼ ';
        $var = $pct >= 0 ? 'var(--color-accent)' : 'var(--color-danger)';
        return ['label' => $dir.number_format($pct, 1, ',', '').' %', 'var' => $var];
    };
    // Shared grid template for the history table header + rows.
    $grid = 'display:grid; grid-template-columns:minmax(150px,180px) 110px 82px 82px 70px minmax(80px,1fr) 108px 104px; gap:0 14px; align-items:center;';
@endphp

@if ($view !== 'tableau')
    {{-- ============================ GRAPHE ============================ --}}
    @php $stats = $this->stats(); @endphp
    <div class="w-full h-full overflow-auto bg-panel p-4 md:p-5">
        @php $custom = $from && $to; @endphp
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="font-sans font-bold text-[18px] text-ink">Production nucléaire nationale</h1>

            {{-- Graphe | Tableau --}}
            <div class="flex gap-0.5 border border-line rounded-lg p-0.5 bg-surface font-sans text-[12px]">
                <button type="button" wire:click="$set('view', 'graphe')"
                    class="px-3 py-1 rounded-md bg-brand text-white font-semibold">Graphe</button>
                <button type="button" wire:click="$set('view', 'tableau')"
                    class="px-3 py-1 rounded-md text-faint hover:text-ink transition-colors">Tableau</button>
            </div>

            <div class="flex-1"></div>
            <div class="flex gap-0.5 border border-line rounded-lg p-0.5 bg-surface font-mono text-[11.5px]">
                @foreach ($this->rangeKeys() as $key)
                    <button type="button" wire:click="selectRange(@js($key))" wire:key="range-{{ $loop->index }}"
                        @class([
                            'px-3 py-1 rounded-md transition-colors',
                            'bg-brand text-white' => ! $custom && $range === $key,
                            'text-faint hover:text-ink' => $custom || $range !== $key,
                        ])>{{ $key }}</button>
                @endforeach
            </div>
            <div @class([
                'flex items-center gap-1.5 font-mono text-[11.5px] border rounded-lg px-2 py-1 bg-surface',
                'border-brand text-ink' => $custom,
                'border-line text-muted' => ! $custom,
            ])>
                <span>du</span>
                <input type="date" wire:model.live="from" max="{{ now()->format('Y-m-d') }}"
                    class="bg-transparent text-ink outline-none [color-scheme:light] dark:[color-scheme:dark]">
                <span>au</span>
                <input type="date" wire:model.live="to" max="{{ now()->format('Y-m-d') }}"
                    class="bg-transparent text-ink outline-none [color-scheme:light] dark:[color-scheme:dark]">
            </div>
        </div>

        <div class="mt-3 bg-surface border border-line rounded-[12px] px-4 pt-3.5 pb-2">
            <div wire:ignore id="history-chart" class="w-full" style="height:290px"></div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 mt-3">
            <div class="bg-surface border border-line rounded-[11px] px-4 py-3">
                <div class="font-sans text-[10.5px] tracking-[0.08em] text-faint">MOYENNE</div>
                <div class="font-mono font-semibold text-[24px] text-ink mt-0.5">{{ $fr1($stats['avg']) }} <span class="text-[13px] text-muted">GW</span></div>
            </div>
            <div class="bg-surface border border-line rounded-[11px] px-4 py-3">
                <div class="font-sans text-[10.5px] tracking-[0.08em] text-faint">MINIMUM</div>
                <div class="font-mono font-semibold text-[24px] text-danger mt-0.5">{{ $fr1($stats['min']) }} <span class="text-[13px] text-muted">GW</span></div>
            </div>
            <div class="bg-surface border border-line rounded-[11px] px-4 py-3">
                <div class="font-sans text-[10.5px] tracking-[0.08em] text-faint">MAXIMUM</div>
                <div class="font-mono font-semibold text-[24px] text-accent mt-0.5">{{ $fr1($stats['max']) }} <span class="text-[13px] text-muted">GW</span></div>
            </div>
            <div class="bg-surface border border-line rounded-[11px] px-4 py-3">
                <div class="font-sans text-[10.5px] tracking-[0.08em] text-faint">FACTEUR DE CHARGE MOYEN</div>
                <div class="font-mono font-semibold text-[24px] text-ink mt-0.5">{{ $stats['fdc'] }} <span class="text-[13px] text-muted">%</span></div>
            </div>
        </div>

        <div class="font-sans font-semibold text-[12px] tracking-[0.1em] text-faint mt-5 mb-2.5">PAR CENTRALE — PROFIL SUR LA PÉRIODE</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
            @foreach ($this->minis() as $mini)
                <a href="{{ route('home') }}" wire:navigate wire:key="mini-{{ $loop->index }}"
                    class="block bg-surface border border-line rounded-[10px] px-3 pt-2 pb-1.5 hover:border-line-strong transition-colors">
                    <div class="flex justify-between items-baseline">
                        <span class="font-sans font-semibold text-[12px] text-ink truncate">{{ $mini['name'] }}</span>
                        <span class="font-mono text-[10.5px] shrink-0" style="color:{{ $mini['color'] }}">{{ $mini['pct'] }} %</span>
                    </div>
                    <div class="[&_svg]:h-[42px]">{!! $mini['spark'] !!}</div>
                </a>
            @endforeach
        </div>
    </div>
@else
    {{-- ============================ TABLEAU (3b) ============================ --}}
    <div class="w-full h-full flex flex-col min-h-0 bg-panel">
        {{-- White sub-toolbar --}}
        <div class="shrink-0 flex items-center gap-3 flex-wrap px-4 md:px-5 py-2.5 bg-surface border-b border-line">
            <div class="flex gap-0.5 border border-line rounded-lg p-0.5 font-sans text-[12px]">
                <button type="button" wire:click="$set('view', 'graphe')"
                    class="px-3 py-1 rounded-md text-faint hover:text-ink transition-colors">Graphe</button>
                <button type="button" wire:click="$set('view', 'tableau')"
                    class="px-3 py-1 rounded-md bg-brand text-white font-semibold">Tableau</button>
            </div>

            <span class="w-px h-5 bg-line"></span>
            <span class="font-mono text-[11px] text-faint">granularité :</span>
            @foreach ($this->grains() as $key => $label)
                <button type="button" wire:click="setGrain(@js($key))" wire:key="grain-{{ $key }}"
                    @class([
                        'px-3 py-[5px] rounded-[14px] font-mono text-[11.5px] border transition-colors',
                        'bg-brand-soft text-brand border-brand/40' => $grain === $key,
                        'bg-surface text-faint border-line hover:text-ink' => $grain !== $key,
                    ])>{{ $label }}</button>
            @endforeach

            <div class="flex-1"></div>
            <span class="font-mono text-[11px] text-faint hidden md:inline">{{ $this->periodLabel() }}</span>
            <button type="button" wire:click="exportCsv"
                class="border border-line-strong rounded-lg px-3 py-1.5 font-sans font-medium text-[12px] text-brand bg-surface hover:border-brand/50 transition-colors">
                ↓ Exporter CSV
            </button>
        </div>

        {{-- Table --}}
        <div class="flex-1 overflow-auto">
            <div class="min-w-[900px]">
                <div class="sticky top-0 z-10 bg-panel border-b border-line px-4 md:px-5 py-2.5 font-sans font-semibold text-[10.5px] tracking-[0.07em] text-faint" style="{{ $grid }}">
                    <span>PÉRIODE</span>
                    <span class="text-right">MOYENNE (GW)</span>
                    <span class="text-right">MIN</span>
                    <span class="text-right">MAX</span>
                    <span class="text-right">FDC</span>
                    <span></span>
                    <span class="text-right">ÉNERGIE (TWh)</span>
                    <span class="text-right">VS PÉRIODE −1</span>
                </div>

                @forelse ($this->monthlyTable as $year)
                    @php $yd = $delta($year['deltaPct']); $hasRows = count($year['rows']) > 0; @endphp
                    <div wire:key="year-{{ $year['year'] }}" x-data="{ open: true }">
                        {{-- Year synthesis (click to collapse) --}}
                        <div @if ($hasRows) x-on:click="open = !open" class="cursor-pointer" @endif
                            class="bg-surface border-b border-line px-4 md:px-5 py-2 hover:bg-panel/60 transition-colors" style="{{ $grid }}">
                            <span class="flex items-center gap-2">
                                @if ($hasRows)
                                    <span class="inline-block text-faint text-[10px] transition-transform w-2.5" x-bind:class="open ? '' : '-rotate-90'">▾</span>
                                @else
                                    <span class="inline-block w-2.5"></span>
                                @endif
                                <span class="font-sans font-bold text-[13px] text-ink">{{ $year['label'] }}</span>
                                <span @class([
                                    'font-mono text-[10px] rounded-full px-2 py-0.5',
                                    'text-danger bg-danger/10' => $year['badgeType'] === 'danger',
                                    'text-muted bg-panel' => $year['badgeType'] !== 'danger',
                                ])>{{ $year['badge'] }}</span>
                            </span>
                            <span class="font-mono font-semibold text-[12.5px] text-ink text-right">{{ $fr1($year['avg']) }}</span>
                            <span class="font-mono text-[11.5px] text-faint text-right">{{ $fr1($year['min']) }}</span>
                            <span class="font-mono text-[11.5px] text-faint text-right">{{ $fr1($year['max']) }}</span>
                            <span class="font-mono text-[11.5px] text-muted text-right">{{ $year['fdc'] }} %</span>
                            <span class="h-[5px] rounded-sm bg-track overflow-hidden">
                                <span class="block h-full rounded-sm bg-brand" style="width:{{ max($year['fdc'], 1) }}%"></span>
                            </span>
                            <span class="font-mono font-semibold text-[12.5px] text-ink text-right">{{ $fr2($year['twh']) }}</span>
                            <span class="font-mono text-[11.5px] text-right" style="color:{{ $yd['var'] }}">{{ $yd['label'] }}</span>
                        </div>

                        {{-- Sub-period rows --}}
                        @if ($hasRows)
                            <div x-show="open" x-collapse>
                                @foreach ($year['rows'] as $row)
                                    @php $rd = $delta($row['deltaPct']); @endphp
                                    <div wire:key="row-{{ $year['year'] }}-{{ $loop->index }}"
                                        class="border-b border-line/60 px-4 md:px-5 py-[7px] hover:bg-panel transition-colors" style="{{ $grid }}">
                                        <span class="font-sans text-[12.5px] text-muted pl-6 capitalize">{{ $row['label'] }}</span>
                                        <span class="font-mono font-medium text-[12px] text-ink text-right">{{ $fr1($row['avg']) }}</span>
                                        <span class="font-mono text-[11.5px] text-faint text-right">{{ $fr1($row['min']) }}</span>
                                        <span class="font-mono text-[11.5px] text-faint text-right">{{ $fr1($row['max']) }}</span>
                                        <span class="font-mono text-[11.5px] text-muted text-right">{{ $row['fdc'] }} %</span>
                                        <span class="h-1 rounded-sm bg-track overflow-hidden">
                                            <span class="block h-full rounded-sm" style="background:{{ \App\Support\LoadColor::var($row['avg'], $row['fdc']) }}; width:{{ max($row['fdc'], 1) }}%"></span>
                                        </span>
                                        <span class="font-mono text-[12px] text-muted text-right">{{ $fr2($row['twh']) }}</span>
                                        <span class="font-mono text-[11.5px] text-right" style="color:{{ $rd['var'] }}">{{ $rd['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-5 py-16 text-center font-sans text-[13px] text-faint">Aucune donnée sur cette période.</div>
                @endforelse
            </div>
        </div>
    </div>
@endif

@script
    <script>
        const mountHistory = (points, meta) => {
            const el = document.getElementById('history-chart');
            if (el && window.createHistoryChart) {
                createHistoryChart(el, points, meta);
            }
        };

        // Initial mount (no-op if we loaded straight into table view).
        mountHistory(@js($this->points()), @js(['len' => $this->currentLen(), 'minTime' => $this->minTime()]));

        $wire.on('history-updated', (event) => {
            const e = Array.isArray(event) ? event[0] : event;
            if (e && e.points) {
                // Wait for the DOM morph (chart div may have just been re-added) before mounting.
                requestAnimationFrame(() => mountHistory(e.points, { len: e.len, minTime: e.minTime }));
            }
        });
    </script>
@endscript
