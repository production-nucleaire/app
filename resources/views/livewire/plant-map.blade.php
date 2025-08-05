<div class="relative w-[calc(100dvw-2rem)] h-[calc(100dvh-2rem)] flex gap-4">
    <div class="w-full max-w-96 flex flex-col gap-4 bg-white rounded-2xl overflow-auto p-4 z-[9999]">
        @if ($selectedPlant)
            <x-plant-selector :selectedPlant="$selectedPlant" :plants="$this->plants" />
            <x-plant-preview :plant="$selectedPlant" />
            <x-plant-reactors :selectedPlant="$selectedPlant" :selectedReactor="$selectedReactor" :day="$day" />
            <x-plant-navigation :previousPlant="$previousPlant" :nextPlant="$nextPlant" />
        @else
            <x-plant-list :plants="$this->plants" />
        @endif
    </div>
    <div wire:ignore id="map" class="w-full h-full rounded-2xl"></div>
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