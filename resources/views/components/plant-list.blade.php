@php
    use App\Support\LoadColor;
    $sorted = $plants->sortByDesc->latest_production_mw->values();
@endphp

<div x-data="{ mode: $persist('grid') }" class="flex flex-col h-full min-h-0">
    <div class="flex items-center justify-between px-4 pt-3 pb-2.5 shrink-0">
        <div class="font-sans font-semibold text-[12px] tracking-[0.1em] text-faint">{{ $sorted->count() }} CENTRALES</div>
        <div class="flex gap-0.5 border border-line rounded-lg p-0.5">
            <button type="button" x-on:click="mode = 'grid'"
                class="px-2.5 py-[3px] rounded-md text-xs leading-none"
                x-bind:class="mode === 'grid' ? 'bg-brand text-white' : 'text-faint hover:text-ink'">▦</button>
            <button type="button" x-on:click="mode = 'list'"
                class="px-2.5 py-[3px] rounded-md text-xs leading-none"
                x-bind:class="mode === 'list' ? 'bg-brand text-white' : 'text-faint hover:text-ink'">☰</button>
        </div>
    </div>

    <div class="flex-1 overflow-auto px-3 pb-3">
        <div x-show="mode === 'grid'" class="grid grid-cols-2 gap-[7px] content-start">
            @foreach ($sorted as $plant)
                @php
                    $pct = round($plant->percent_value);
                    $mw = $plant->latest_production_mw;
                    $color = LoadColor::var($mw, $pct);
                @endphp
                <a href="{{ route('plant', ['slug' => $plant->slug]) }}" wire:navigate wire:key="grid-{{ $plant->id }}"
                    class="block border border-line rounded-[10px] px-3 py-2.5 bg-surface hover:border-line-strong transition-colors">
                    <div class="flex items-center justify-between gap-1">
                        <span class="font-sans font-semibold text-[13px] text-ink truncate">{{ $plant->name }}</span>
                        <span class="font-mono font-medium text-[10px] text-faint shrink-0">{{ $plant->active_reactors_count }}/{{ $plant->reactors->count() }}</span>
                    </div>
                    <div class="h-1 rounded-sm bg-track overflow-hidden my-2">
                        <div class="h-full rounded-sm" style="background:{{ $color }}; width:{{ max($pct, 1) }}%"></div>
                    </div>
                    <div class="flex justify-between items-baseline">
                        <span class="font-mono font-semibold text-[12.5px] text-ink">{{ Number::format($mw, locale: 'fr') }}&nbsp;MW</span>
                        <span class="font-mono font-medium text-[11px] text-faint">{{ $pct }}&nbsp;%</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div x-show="mode === 'list'" x-cloak class="flex flex-col gap-[5px]">
            @foreach ($sorted as $plant)
                @php
                    $pct = round($plant->percent_value);
                    $mw = $plant->latest_production_mw;
                    $color = LoadColor::var($mw, $pct);
                @endphp
                <a href="{{ route('plant', ['slug' => $plant->slug]) }}" wire:navigate wire:key="list-{{ $plant->id }}"
                    class="flex items-center gap-2.5 px-2.5 py-[7px] rounded-[9px] hover:bg-panel transition-colors">
                    <div class="w-[26px] h-[26px] rounded-full shrink-0 grid place-items-center text-white font-mono font-semibold text-[10px]" style="background:{{ $color }}">
                        {{ $plant->active_reactors_count }}<span class="opacity-60">/{{ $plant->reactors->count() }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline gap-1">
                            <span class="font-sans font-semibold text-[13px] text-ink truncate">{{ $plant->name }}</span>
                            <span class="font-mono font-semibold text-[12.5px] text-ink shrink-0">{{ Number::format($mw, locale: 'fr') }}&nbsp;MW</span>
                        </div>
                        <div class="flex items-center gap-2 mt-[3px]">
                            <div class="flex-1 h-1 rounded-sm bg-track overflow-hidden">
                                <div class="h-full rounded-sm" style="background:{{ $color }}; width:{{ max($pct, 1) }}%"></div>
                            </div>
                            <span class="font-mono font-medium text-[10.5px] text-faint w-[34px] text-right">{{ $pct }}&nbsp;%</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
