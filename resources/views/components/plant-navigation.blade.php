<div class="flex items-center justify-between border-t border-t-slate-200 pt-2 mt-auto">
    @if ($previousPlant)
        <a href="{{ route('welcome', ['plant' => $previousPlant->id]) }}" class="h-8 flex items-center justify-center gap-1 bg-white hover:bg-slate-200 rounded text-xs font-medium text-slate-600 hover:text-slate-800 uppercase px-3 mr-auto" wire:click.prevent="$set('selectedPlantId', {{ $previousPlant->id }})">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3 h-3 fill-slate-600"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M163 320L185.6 342.6L377.6 534.6L400.2 557.2L445.5 511.9L422.9 489.3L253.5 319.9L422.9 150.5L445.5 127.9L400.2 82.6L377.6 105.2L185.6 297.2L163 319.8z"/></svg>
            {{ $previousPlant->name }}
        </a>
    @endif
    @if ($nextPlant)
        <a href="{{ route('welcome', ['plant' => $nextPlant->id]) }}" class="h-8 flex items-center justify-center gap-1 bg-white hover:bg-slate-200 rounded text-xs font-medium text-slate-600 hover:text-slate-800 uppercase px-3 ml-auto" wire:click.prevent="$set('selectedPlantId', {{ $nextPlant->id }})">
            {{ $nextPlant->name }}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3 h-3 fill-slate-600"><path d="M477.5 320L454.9 342.6L262.9 534.6L240.3 557.3L195 512L217.6 489.4L387 320L217.6 150.6L195 128L240.3 82.7L262.9 105.4L454.9 297.4L477.5 320z"/></svg>
        </a>
    @endif
</div>