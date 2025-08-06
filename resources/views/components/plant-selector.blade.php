<div x-data="{ open: false }" x-on:click.away="open = false" x-on:keydown.Escape="open = false" class="relative">
    <button type="button" class="group relative w-full flex items-center justify-between border border-slate-200 dark:border-slate-500 group-hover:border-slate-300 dark:group-hover:border-slate-400 rounded-lg text-sm font-medium text-slate-900 dark:text-slate-200 uppercase cursor-pointer p-3" x-on:click="open = !open">
        {{ $selectedPlant->name }}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3.5 h-3.5 fill-slate-600 dark:fill-slate-400 group-hover:fill-slate-800 dark:group-hover:fill-slate-200"><path d="M320.4 489.9L337.4 472.9L537.4 272.9L554.4 255.9L520.5 222L503.5 239L320.5 422L137.5 239L120.5 222L86.6 255.9L103.6 272.9L303.6 472.9L320.6 489.9z"/></svg>
    </button>
    <ul class="absolute left-0 right-0 top-10 max-h-52 overflow-auto flex flex-col gap-2 bg-white dark:bg-slate-800 border border-t-0 border-slate-200 rounded-b-lg p-3 z-50" x-show="open" x-cloak>
        @foreach ($this->plants as $plant)
            @if ($plant['id'] !== $selectedPlant->id)
                <li wire:key="selectable-plant-{{ $plant['id'] }}">
                    <button class="w-full flex items-center text-sm text-slate-500 hover:text-slate-900 cursor-pointer" x-on:click="open = false; $wire.set('selectedPlantId', {{ $plant['id'] }})">
                        {{ $plant['name'] }}
                    </button>
                </li>
            @endif
        @endforeach
    </ul>
</div>