<div x-data="{ open: false }" x-on:click.away="open = false" x-on:keydown.Escape="open = false" class="relative">
    <button type="button" class="group relative w-full flex items-center justify-between border border-slate-200 group-hover:border-slate-300 rounded-lg font-medium text-sm uppercase cursor-pointer p-3" x-on:click="open = !open">
        {{ $selectedPlant->name }}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3.5 h-3.5 fill-slate-600 group-hover:fill-slate-800"><path d="M320.4 489.9L337.4 472.9L537.4 272.9L554.4 255.9L520.5 222L503.5 239L320.5 422L137.5 239L120.5 222L86.6 255.9L103.6 272.9L303.6 472.9L320.6 489.9z"/></svg>
        <div class="absolute right-6 top-1/2 w-8 h-8 -translate-y-1.5" x-on:click="$wire.set('selectedPlantId', 0); open = false">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3.5 h-3.5 fill-slate-500 group-hover:fill-slate-800"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M182.9 137.4L160.3 114.7L115 160L137.6 182.6L275 320L137.6 457.4L115 480L160.3 525.3L182.9 502.6L320.3 365.3L457.6 502.6L480.3 525.3L525.5 480L502.9 457.4L365.5 320L502.9 182.6L525.5 160L480.3 114.7L457.6 137.4L320.3 274.7L182.9 137.4z"/></svg>
        </div>
    </button>
    <ul class="absolute left-0 right-0 top-10 max-h-52 overflow-auto flex flex-col gap-2 bg-white border border-t-0 border-slate-200 rounded-b-lg p-3 z-50" x-show="open" x-cloak>
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