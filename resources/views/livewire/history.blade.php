@php
    $fr1 = fn ($v) => number_format($v, 1, ',', "\u{202f}");
    $stats = $this->stats();
@endphp

<div class="w-full h-full overflow-auto bg-panel p-4 md:p-5">
    @php $custom = $from && $to; @endphp
    <div class="flex items-center gap-3 flex-wrap">
        <h1 class="font-sans font-bold text-[18px] text-ink">Production nucléaire nationale</h1>
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

@script
    <script>
        const historyEl = document.getElementById('history-chart');
        const historyMeta = @js(['len' => $this->currentLen(), 'minTime' => $this->minTime()]);
        if (historyEl && window.createHistoryChart) {
            createHistoryChart(historyEl, @js($this->points()), historyMeta);
        }

        $wire.on('history-updated', (event) => {
            const e = Array.isArray(event) ? event[0] : event;
            if (historyEl && window.createHistoryChart && e && e.points) {
                createHistoryChart(historyEl, e.points, { len: e.len, minTime: e.minTime });
            }
        });
    </script>
@endscript
