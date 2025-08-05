<div class="relative w-screen h-screen flex gap-4 p-4">
    <div class="w-full max-w-96 flex flex-col gap-4 bg-white rounded-2xl overflow-auto p-4 z-[9999]">
        @if ($selectedPlant)
            <div x-data="{ open: false }" x-on:click.away="open = false" x-on:keydown.Escape="open = false" class="relative">
                <button type="button" class="w-full flex items-center justify-between border border-slate-200 rounded-lg font-medium text-sm uppercase cursor-pointer p-3" x-on:click="open = !open">
                    {{ $selectedPlant->name }}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3.5 h-3.5 fill-slate-800"><path d="M320.4 489.9L337.4 472.9L537.4 272.9L554.4 255.9L520.5 222L503.5 239L320.5 422L137.5 239L120.5 222L86.6 255.9L103.6 272.9L303.6 472.9L320.6 489.9z"/></svg>
                </button>
                <ul class="absolute left-0 right-0 top-12 max-h-52 overflow-auto flex flex-col gap-2 bg-white border border-t-0 border-slate-200 rounded-b-lg p-3 z-50" x-show="open" x-cloak>
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
            <div class="relative rounded-lg overflow-hidden flex-shrink-0">
                <div class="absolute bottom-0 right-0 bg-slate-950/65 rounded-tl-2xl text-xs font-medium text-white py-1 px-4">&copy; EDF</div>
                <img class="h-36 object-cover" src="{{ Vite::asset('resources/images/centrale-' . str($selectedPlant->name)->lower() . '.jpg') }}" alt="">
            </div>
            <div class="flex items-center justify-between gap-2">
                <div class="h-7.5 flex items-center gap-2 bg-blue-50 border border-blue-200 text-blue-600 rounded px-2 py-1 text-xs font-semibold uppercase px-3 [&_svg]:w-4 [&_svg]:h-4 [&_svg]:fill-blue-600">
                    @if ('SEA' === $selectedPlant->cooling_type)
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path d="m56.5 43c-3 0-5.9-.8-8.2-2.3-2.3 1.5-5.1 2.3-8.1 2.3s-5.9-.8-8.2-2.3c-2.3 1.5-5.2 2.3-8.2 2.3s-5.9-.8-8.2-2.3c-2.3 1.5-5.2 2.3-8.2 2.3-2 0-4.1-.4-5.9-1.1-.5-.2-.8-.8-.6-1.3s.8-.8 1.3-.6c1.6.6 3.3 1 5.1 1 2.8 0 5.5-.8 7.6-2.3.4-.3.8-.3 1.2 0 2 1.5 4.7 2.3 7.6 2.3 2.8 0 5.5-.8 7.6-2.3.4-.3.8-.3 1.2 0 2 1.5 4.7 2.3 7.6 2.3s5.5-.8 7.6-2.3c.4-.3.8-.3 1.2 0 2 1.5 4.7 2.3 7.6 2.3 1.8 0 3.6-.3 5.1-1 .5-.2 1.1 0 1.3.6.2.5 0 1.1-.6 1.3-1.7.7-3.8 1.1-5.8 1.1z"></path><path d="m56.5 49c-3 0-5.9-.8-8.2-2.3-2.3 1.5-5.1 2.3-8.1 2.3s-5.9-.8-8.2-2.3c-2.3 1.5-5.2 2.3-8.2 2.3s-5.9-.8-8.2-2.3c-2.3 1.5-5.2 2.3-8.2 2.3-2 0-4.1-.4-5.9-1.1-.5-.2-.8-.8-.6-1.3s.8-.8 1.3-.6c1.6.6 3.3 1 5.1 1 2.8 0 5.5-.8 7.6-2.3.4-.3.8-.3 1.2 0 2 1.5 4.7 2.3 7.6 2.3 2.8 0 5.5-.8 7.6-2.3.4-.3.8-.3 1.2 0 2 1.5 4.7 2.3 7.6 2.3s5.5-.8 7.6-2.3c.4-.3.8-.3 1.2 0 2 1.5 4.7 2.3 7.6 2.3 1.8 0 3.6-.3 5.1-1 .5-.2 1.1 0 1.3.6s0 1.1-.6 1.3c-1.7.7-3.8 1.1-5.8 1.1z"></path><path d="m57.3 31.3c-1.1 0-2.2-.2-3.3-.8 0 0-.1-.1-.1-.1-1.6-1.1-2.3-2.8-1.8-4.6.4-1.5 1.5-2.7 2.9-3.4-.7-.5-1.5-.9-2.4-.9-2-.2-4.4.9-6.6 3-3.3 3.1-8.8 5.7-12.6 4.7-1.9-.5-3.2-1.9-3.7-3.9-.6-2.3 0-4.4 1.6-5.7s3.9-1.5 6.3-.7c-.3-2.3-2-4.4-4.6-5.7-3.3-1.7-8.6-1.9-13.3 2.5-2.6 2.7-4 4.9-5.2 6.9-2.4 3.9-3.9 6.4-12.2 7.4-.6.1-1-.3-1.1-.9s.3-1 .9-1.1c7.3-.9 8.5-2.8 10.7-6.5 1.2-2 2.7-4.4 5.5-7.3 5.5-5.2 11.6-4.9 15.6-2.9 3.9 2 6.1 5.6 5.7 9.2 0 .3-.2.6-.5.8s-.6.1-.9 0c-2.1-1.2-4.2-1.2-5.5-.2-1 .8-1.3 2.1-.9 3.6.4 1.3 1.1 2.2 2.3 2.5 3 .8 7.8-1.5 10.7-4.2 2.7-2.5 5.6-3.8 8.2-3.6 1.9.2 3.6 1.1 4.9 2.8.2.3.3.7.1 1-.1.3-.5.6-.8.6-1.3.1-2.6 1.1-2.9 2.4-.2.6-.1 1.6 1 2.4 1.9.9 3.6.7 6.7-.6.5-.2 1.1 0 1.3.5s0 1.1-.5 1.3c-2.3 1-3.9 1.5-5.5 1.5z"></path><path d="m56.5 37c-3 0-5.9-.8-8.2-2.3-2.3 1.5-5.1 2.3-8.1 2.3s-5.9-.8-8.2-2.3c-2.3 1.5-5.2 2.3-8.2 2.3s-5.9-.8-8.2-2.3c-2.3 1.5-5.2 2.3-8.2 2.3-2 0-4.1-.4-5.9-1.1-.5-.2-.8-.8-.6-1.3.4-.6 1-.8 1.5-.6 1.6.6 3.3 1 5.1 1 2.8 0 5.5-.8 7.6-2.3.4-.3.8-.3 1.2 0 2 1.5 4.7 2.3 7.6 2.3 2.8 0 5.5-.8 7.6-2.3.4-.3.8-.3 1.2 0 2 1.5 4.7 2.3 7.6 2.3s5.5-.8 7.6-2.3c.4-.3.8-.3 1.2 0 2 1.5 4.7 2.3 7.6 2.3 1.8 0 3.6-.3 5.1-1 .5-.2 1.1 0 1.3.6.2.5 0 1.1-.6 1.3-1.9.7-4 1.1-6 1.1z"></path></svg>
                        Mer
                    @elseif ('RIVER' === $selectedPlant->cooling_type)
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M458.27,125.52c-17.151-12.473-36.589-26.611-74.264-26.611c-37.675,0-57.115,14.137-74.264,26.611c-15.315,11.137-27.409,19.934-53.733,19.934c-26.326,0-38.422-8.797-53.737-19.934c-17.151-12.474-36.591-26.611-74.268-26.611c-37.677,0-57.117,14.137-74.268,26.611C38.421,136.657,26.325,145.455,0,145.455v64c26.325,0,38.421-8.797,53.736-19.934c17.151-12.474,36.592-26.611,74.268-26.611s57.116,14.137,74.268,26.611c15.315,11.137,27.411,19.934,53.737,19.934c26.324,0,38.42-8.797,53.733-19.934c17.15-12.474,36.589-26.611,74.264-26.611c37.675,0,57.114,14.138,74.264,26.611c15.312,11.137,27.408,19.934,53.73,19.934v-64C485.679,145.455,473.583,136.657,458.27,125.52z"></path><path d="M437.738,322.48c-15.313-11.137-27.409-19.934-53.732-19.934c-26.324,0-38.42,8.797-53.733,19.934c-17.15,12.474-36.589,26.611-74.264,26.611c-37.676,0-57.117-14.137-74.268-26.611c-15.316-11.137-27.412-19.934-53.737-19.934c-26.326,0-38.422,8.797-53.737,19.934C57.116,334.954,37.676,349.091,0,349.091v64c37.676,0,57.116-14.137,74.268-26.611c15.315-11.137,27.411-19.934,53.736-19.934s38.421,8.797,53.736,19.934c17.152,12.474,36.592,26.611,74.269,26.611c37.675,0,57.115-14.137,74.264-26.611c15.313-11.137,27.409-19.934,53.733-19.934c26.323,0,38.419,8.797,53.732,19.934c17.15,12.474,36.588,26.611,74.262,26.611v-64C474.326,349.091,454.888,334.954,437.738,322.48z"></path><path d="M437.738,217.752c-15.313-11.137-27.409-19.934-53.732-19.934c-26.324,0-38.42,8.797-53.733,19.934c-17.15,12.474-36.589,26.611-74.264,26.611c-37.676,0-57.117-14.137-74.268-26.611c-15.316-11.137-27.412-19.934-53.737-19.934c-26.326,0-38.422,8.797-53.737,19.934C57.116,230.227,37.676,244.364,0,244.364v69.818c26.325,0,38.421-8.797,53.736-19.934c17.151-12.474,36.592-26.611,74.268-26.611s57.116,14.137,74.268,26.611c15.315,11.137,27.411,19.934,53.737,19.934c26.324,0,38.42-8.797,53.733-19.934c17.15-12.474,36.589-26.611,74.264-26.611c37.675,0,57.114,14.138,74.264,26.611c15.312,11.137,27.408,19.934,53.73,19.934v-69.818C474.326,244.364,454.888,230.227,437.738,217.752z"></path></svg>
                        {{ $selectedPlant->cooling_place }}
                    @endif
                </div>
                <div class="flex items-center justify-end gap-2">
                    <a class="w-8 h-8 flex items-center justify-around bg-white border border-slate-200 rounded" href="{{ $selectedPlant->edf_link }}" target="_blank" rel="noopener noreferrer">
                        <img class="w-4 object-cover" src="{{ Vite::asset('resources/images/logo-edf.png') }}" alt="">
                    </a>
                    <a class="w-8 h-8 flex items-center justify-around bg-white border border-slate-200 rounded" href="{{ $selectedPlant->wiki_link }}" target="_blank" rel="noopener noreferrer">
                        <img class="w-5 object-cover" src="{{ Vite::asset('resources/images/logo-wiki.svg') }}" alt="">
                    </a>
                </div>
            </div>
            <div class="">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-700 mb-2">
                    Production&nbsp;(MW)
                    <span class="w-full h-px bg-slate-20 mt-0.5"></span>
                </div>
                <div class="flex flex-col gap-2">
                    @foreach ($selectedPlant->reactors as $reactor)
                        @if ($selectedReactor && $selectedReactor->is($reactor))
                            <div class="bg-white border border-slate-200 rounded p-2">
                                <x-reactor-preview :reactor="$reactor" />
                                <x-reactor-production-chart :reactor="$reactor" :day="$day" />
                            </div>
                        @else
                            <a href="{{ route('welcome', ['plant' => $selectedPlant->id, 'reactor' => $reactor->id]) }}" wire:click.prevent="$set('selectedReactorId', {{ $reactor->id }})" class="bg-slate-50 hover:bg-slate-200 border border-slate-50 rounded p-2">
                                <x-reactor-preview :reactor="$reactor" />
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
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
        @else
            @foreach ($this->plants as $plant)
                <div wire:key="plant-{{ $plant['id'] }}" class="border-b border-slate-200 py-2">
                    <h3 class="font-semibold">{{ $plant['name'] }}</h3>
                    <p class="text-sm text-slate-500">Active Reactors: {{ $plant['active_reactors'] }}</p>
                </div>
            @endforeach
        @endif
    </div>
    <div wire:ignore id="map" class="w-full h-full rounded-2xl"></div>
</div>

@assets
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>
@endassets

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