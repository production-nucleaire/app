<div class="relative w-[calc(100dvw-1rem)] h-[calc(100dvh-1rem)] flex gap-2">
    <div class="w-full max-w-96 flex flex-col gap-4 bg-white rounded-md overflow-auto p-4 z-[9999]">
        @if ($selectedPlant)
            <x-plant-selector :selectedPlant="$selectedPlant" :plants="$this->plants" />
            <x-plant-preview :plant="$selectedPlant" />
            <x-plant-reactors :selectedPlant="$selectedPlant" :selectedReactor="$selectedReactor" :day="$day" />
            <x-plant-navigation :previousPlant="$previousPlant" :nextPlant="$nextPlant" />
        @else
            <x-plant-list :plants="$this->markers" />
        @endif
    </div>
    <div class="w-full h-full flex flex-col gap-2">
        <div wire:ignore id="map" class="w-full h-full flex flex-col items-center justify-center bg-white rounded-md">
            <img class="w-16 h-16 animate-pulse" src="{{ Vite::asset('resources/images/logo.svg') }}" alt="" />
            <div class="flex flex-col items-center justify-center mt-4">
                <div class="text-xs font-medium text-slate-500">Chargement de la carte...</div>
            </div>
            <noscript>
                <span class="text-xs font-medium text-rose-500">JavaScript est désactivé</span>
            </noscript>
        </div>
        <div class="h-8 flex items-center justify-between bg-white rounded-md text-[.65rem] font-medium text-slate-600 px-4">
            <span @class([
                'flex items-center gap-1.5 [&_svg]:w-3 [&_svg]:h-3',
                'text-rose-900 [&_svg]:fill-rose-700' => now()->subHours(6) >= $lastUpdated,
                'text-amber-900 [&_svg]:fill-amber-700' => now()->subHours(2) >= $lastUpdated && now()->subHours(6) < $lastUpdated,
                'text-green-900 [&_svg]:fill-green-700' => now()->subHours(2) < $lastUpdated,
            ])>
                @if (now()->subHour() <= $lastUpdated)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="fill-green-500"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM417.1 256.4L404.4 276.8L324.4 404.8L317.4 416.1L292.1 416.1L284.9 406.5C247.3 356.4 226.5 328.6 222.5 323.3L260.9 294.5C268.4 304.5 282.2 322.9 302.3 349.7C351.4 271.2 376.1 231.6 376.5 231L417.2 256.4z"/></svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM344 208L344 352L296 352L296 208L344 208zM296 440L296 392L344 392L344 440L296 440z"/></svg>
                @endif
                <span>Dernière mise à jour le <strong>{{ $lastUpdated->format('d/m/Y') }}</strong> à <strong>{{ $lastUpdated->format('H:i') }}</strong>. <a class="text-slate-800 hover:underline" href="">En savoir +</a></span>
            </span>
            <span>Données <a class="text-blue-500 hover:underline" href="https://data.rte-france.com/" target="_blank" rel="noopener noreferrer">RTE</a>.</span>
        </div>
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