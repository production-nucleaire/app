<div
    class="relative"
    x-data="{
        open: false,
        plants: [],
        selected: 0,
        init() {
            this.plants = this.$el.querySelectorAll('ul li button');
            this.selected = this.$el.querySelector('button.active').getAttribute('data-selector-id');
            console.log(this.selected);
        },
        selectNext() {
            let currentIndex = Array.from(this.plants).findIndex(plant => plant.getAttribute('data-selector-id') == this.selected);
            if (currentIndex < this.plants.length - 1) {
                this.selected = this.plants[currentIndex + 1].getAttribute('data-selector-id');
                this.plants[currentIndex].classList.remove('active');
                this.plants[currentIndex + 1].classList.add('active');
            } else {
                this.selected = this.plants[0].getAttribute('data-selector-id');
                this.plants[currentIndex].classList.remove('active');
                this.plants[0].classList.add('active');
            }
            this.plants[currentIndex].scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        },
        selectPrevious() {
            let currentIndex = Array.from(this.plants).findIndex(plant => plant.getAttribute('data-selector-id') == this.selected);
            if (currentIndex > 0) {
                this.selected = this.plants[currentIndex - 1].getAttribute('data-selector-id');
                this.plants[currentIndex].classList.remove('active');
                this.plants[currentIndex - 1].classList.add('active');
            } else {
                this.selected = this.plants[this.plants.length - 1].getAttribute('data-selector-id');
                this.plants[currentIndex].classList.remove('active');
                this.plants[this.plants.length - 1].classList.add('active');
            }
            this.plants[currentIndex].scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        }
    }"
    x-on:click.away="open = false"
    x-on:keydown.Escape="open = false"
    x-on:keydown.enter.prevent="$wire.set('selectedPlantId', selected); open = false"
>
    <button type="button" class="group relative w-full flex items-center justify-between border border-slate-200 dark:border-slate-500 group-hover:border-slate-300 dark:group-hover:border-slate-400 rounded-lg text-sm font-medium text-slate-900 dark:text-slate-200 uppercase cursor-pointer p-3" x-on:click="open = !open">
        {{ $selectedPlant->name }}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3.5 h-3.5 fill-slate-600 dark:fill-slate-400 group-hover:fill-slate-800 dark:group-hover:fill-slate-200"><path d="M320.4 489.9L337.4 472.9L537.4 272.9L554.4 255.9L520.5 222L503.5 239L320.5 422L137.5 239L120.5 222L86.6 255.9L103.6 272.9L303.6 472.9L320.6 489.9z"/></svg>
    </button>
    <ul class="absolute left-0 right-0 top-10 max-h-52 overflow-auto flex flex-col gap-1 bg-white dark:bg-slate-800 border border-t-0 border-slate-200 rounded-b-lg p-2 z-50" x-show="open" x-cloak>
        @foreach ($this->plants as $plant)
            <li wire:key="selectable-plant-{{ $plant['id'] }}">
                <button
                    @class([
                        'w-full h-12 flex items-center justify-between bg-slate-50 hover:bg-slate-100 rounded text-sm text-slate-500 hover:text-slate-900 cursor-pointer px-1.5 py-1',
                        'active' => $selectedPlant->id === $plant['id']
                    ])
                    data-selector-id="{{ $plant['id'] }}"
                    x-bind:class="selected === '{{ $plant['id'] }}' ? 'bg-slate-100! dark:bg-slate-700! text-slate-900!' : ''" x-on:click="open = false; $wire.set('selectedPlantId', {{ $plant['id'] }})"
                >
                    <div class="text-left">
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $plant->name }}</h3>
                        <div class="text-[.65rem] font-semibold text-slate-600 dark:text-slate-400">{{ sprintf('%d tranche%s sur %d', $plant->active_reactors_count, $plant->active_reactors_count === 1 ? '' : 's', $plant->reactors->count()) }}</div>
                        {{-- <div>{{ Number::format($plant->latest_production_mw, locale: 'fr') . ' sur ' . Number::format($plant->total_production_mw, locale: 'fr') }} MW</div> --}}
                    </div>
                    <div class="flex flex-col items-center flex-shrink-0">
                        <div class="w-24">
                            <x-plant-production-chart :plant="$plant" />
                        </div>
                        <div class="text-[.65rem] font-bold text-slate-500 -mt-1">
                            {{ Number::format($plant->latest_production_mw, locale: 'fr') }}&nbsp;MW ({{ round($plant->percent_value) }}&nbsp;%)
                        </div>
                    </div>
                </button>
            </li>
        @endforeach
    </ul>
</div>