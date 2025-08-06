<div class="">
    <div class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">
        Production&nbsp;(MW)
        <span class="w-full h-px bg-slate-20 mt-0.5"></span>
    </div>
    <div class="flex flex-col gap-2">
        @foreach ($selectedPlant->reactors as $reactor)
            @if ($selectedReactor && $selectedReactor->is($reactor))
                <div wire:key="plant-{{ $selectedPlant->id }}-reactor-{{ $reactor->id }}" class="bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded p-2">
                    <x-reactor-preview :reactor="$reactor" />
                    <x-reactor-production-chart :reactor="$reactor" :day="$day" />
                </div>
            @else
                <a wire:key="plant-{{ $selectedPlant->id }}-reactor-{{ $reactor->id }}" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 dark:hover:border-slate-600 rounded p-2" href="{{ route('reactor', ['slug' => $selectedPlant->slug, 'reactor' => $reactor->reactor_index]) }}" wire:click.prevent="$set('selectedReactorId', {{ $reactor->id }})" class="bg-slate-50 dark:bg-slate-800 hover:bg-slate-200 border border-slate-50 rounded p-2">
                    <x-reactor-preview :reactor="$reactor" />
                </a>
            @endif
        @endforeach
    </div>
</div>