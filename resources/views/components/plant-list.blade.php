<div x-data="{ mode: $persist('grid') }" class="flex flex-col gap-4">
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <button class="cursor-pointer" type="button" x-on:click="mode = 'grid'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4" x-bind:class="mode == 'grid' ? 'fill-slate-800 dark:fill-slate-200' : 'fill-slate-400 dark:fill-slate-400'"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M288 96L96 96L96 288L288 288L288 96zM288 352L96 352L96 544L288 544L288 352zM352 96L352 288L544 288L544 96L352 96zM544 352L352 352L352 544L544 544L544 352z"/></svg>
            </button>
            <button class="cursor-pointer" type="button" x-on:click="mode = 'list'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4" x-bind:class="mode == 'list' ? 'fill-slate-800 dark:fill-slate-200' : 'fill-slate-400 dark:fill-slate-400'"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M176 112L80 112L80 208L176 208L176 112zM256 128L224 128L224 192L576 192L576 128L256 128zM256 288L224 288L224 352L576 352L576 288L256 288zM256 448L224 448L224 512L576 512L576 448L256 448zM80 272L80 368L176 368L176 272L80 272zM176 432L80 432L80 528L176 528L176 432z"/></svg>
            </button>
        </div>
        <div class="w-full h-px bg-slate-200 dark:bg-slate-700"></div>
        <div></div>
    </div>
    <div class="grid gap-2" x-bind:class="mode == 'list' ? 'grid-cols-1' : 'grid-cols-2 md:grid-cols-3'">
        {{-- Loop through each plant and display it --}}
        @foreach ($plants as $plant)
            <a
                class="flex items-center justify-between bg-slate-50 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-50 dark:border-slate-700 dark:hover:border-slate-600 rounded p-2"
                href="{{ route('plant', ['slug' => $plant['slug']]) }}"
                wire:key="plant-{{ $plant['id'] }}"
                wire:click.prevent="$set('selectedPlantId', {{ $plant['id'] }})"
                x-on:mouseover="highlightPlantMarker({{ $plant['id'] }})"
                x-on:mouseout="unhighlightPlantMarker({{ $plant['id'] }})"
                x-bind:class="mode == 'grid' ? 'flex-col gap-2' : 'flex-row'"
            >
                <div>
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $plant['name'] }}</h3>
                    <div class="text-[.65rem] font-semibold text-slate-600 dark:text-slate-400">{{ sprintf('%d tranche%s sur %d', $plant['active_reactors'], $plant['active_reactors'] === 1 ? '' : 's', $plant['total_reactors']) }}</div>
                    {{-- <div>{{ Number::format($plant['latest_production_mw'], locale: 'fr') . ' sur ' . Number::format($plant['total_production_mw'], locale: 'fr') }} MW</div> --}}
                </div>
                <div class="flex flex-col items-center flex-shrink-0">
                    <div class="w-24">
                        <x-plant-production-chart :plant="$plant['id']" />
                    </div>
                    <div class="text-[.65rem] font-bold text-slate-500 -mt-1">
                        {{ Number::format($plant['latest_production_mw'], locale: 'fr') }}&nbsp;MW ({{ round($plant['percent_value']) }}&nbsp;%)
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>