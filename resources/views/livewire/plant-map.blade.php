<div class="relative w-[calc(100dvw-2rem)] h-[calc(100dvh-2rem)] flex gap-4">
    <div class="w-full max-w-96 flex flex-col gap-4 bg-white rounded-2xl overflow-auto p-4 z-[9999]">
        @if ($selectedPlant)
            <x-plant-selector :selectedPlant="$selectedPlant" :plants="$this->plants" />
            <x-plant-preview :plant="$selectedPlant" />
            <x-plant-reactors :selectedPlant="$selectedPlant" :selectedReactor="$selectedReactor" :day="$day" />
            <x-plant-navigation :previousPlant="$previousPlant" :nextPlant="$nextPlant" />
        @else
            <x-plant-list :plants="$this->markers" />
        @endif
    </div>
    <div wire:ignore id="map" class="w-full h-full flex flex-col items-center justify-center bg-white rounded-2xl">
        <img class="w-16 h-16 animate-pulse" src="{{ Vite::asset('resources/images/logo.svg') }}" alt="" />
        <div class="flex flex-col items-center justify-center mt-4">
            <div class="text-xs font-medium text-slate-500">Chargement de la carte...</div>
        </div>
        <noscript>
            <span class="text-xs font-medium text-rose-500">JavaScript est désactivé</span>
        </noscript>
    </div>
</div>

@script
    <script>
        const plants = @json($this->markers),
        selectedPlantId = @js($this->selectedPlantId);

        createPlantMap(plants);

        if (selectedPlantId) {
            selectPlantMarker(selectedPlantId);
        }
    </script>
@endscript