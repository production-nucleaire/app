<?php

namespace App\Livewire;

use App\Models\Plant;
use App\Models\Reactor;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

class PlantMap extends Component
{
    #[Url(as: 'day')]
    public ?string $day = null;

    public int $selectedPlantId = 0;

    public int $selectedReactorId = 0;

    public ?Plant $previousPlant = null;
    public ?Plant $nextPlant = null;

    public ?Plant $selectedPlant = null;
    public ?Reactor $selectedReactor = null;

    public ?Carbon $lastUpdated = null;

    public function mount(?string $slug = null, ?int $reactor = null)
    {
        if ($slug) {
            $plant = Plant::query()
                ->with(['reactors.latestRecord'])
                ->where('slug', $slug)
                ->orWhere('name', $slug)
                ->first();

            if ($plant) {
                $this->selectedPlantId = $plant->id;
                if ($reactor) {
                    $reactor = Reactor::where('plant_id', $this->selectedPlantId)
                        ->where('reactor_index', $reactor)
                        ->first();
                    if ($reactor) {
                        $this->selectedReactorId = $reactor->id;
                    }
                }
            }
        }

        if ($this->selectedPlantId) {
            $this->selectedPlant = $this->plants->firstWhere('id', $this->selectedPlantId);
            $this->setNavigation();
        }

        if ($this->selectedReactorId) {
            $this->selectedReactor = $this->selectedPlant?->reactors->firstWhere('id', $this->selectedReactorId);
        }

        $lastUpdated = cache('rte:last_successful_import');
        if (!$lastUpdated) {
            $lastUpdated = \App\Models\Record::latest('date')->value('date');
            if ($lastUpdated) {
                cache()->forever('rte:last_successful_import', $lastUpdated->format('Y-m-d H:i:s'));
            }
        } else {
            $lastUpdated = Carbon::parse($lastUpdated);
        }

        $this->lastUpdated = $lastUpdated;
    }

    public function setNavigation()
    {
        $index = $this->plants->search(fn ($p) => $p->id === $this->selectedPlantId);
        $this->previousPlant = $this->plants->get($index - 1);
        $this->nextPlant = $this->plants->get($index + 1);
    }

    public function updatedSelectedPlantId($value)
    {
        $this->selectedPlant = $this->plants->firstWhere('id', $value);
        $this->setNavigation();

        $this->selectedReactorId = 0;
        $this->selectedReactor = null;

        $this->dispatch('plant-selected', [
            'plantId' => $this->selectedPlantId,
            'slug' => $this->selectedPlant?->slug,
        ]);
    }

    public function updatedSelectedReactorId($value)
    {
        $this->selectedReactor = $this->selectedPlant?->reactors->firstWhere('id', $value);

        $this->dispatch('reactor-selected', [
            'slug' => $this->selectedPlant->slug,
            'reactor' => $this->selectedReactor?->reactor_index,
        ]);
    }

    #[On('select-plant')]
    public function selectPlant($plantId)
    {
        $this->selectedPlantId = $plantId;
        $this->selectedPlant = $this->plants->firstWhere('id', $plantId);
        $this->setNavigation();

        $this->dispatch('plant-selected', [
            'plantId' => $plantId,
            'slug' => $this->selectedPlant?->slug,
        ]);
    }

    #[Computed]
    public function markers()
    {
        return $this->plants
            ->map(fn ($plant) => [
                'id' => $plant->id,
                'name' => $plant->name,
                'slug' => $plant->slug,
                'lat' => $plant->latitude,
                'lng' => $plant->longitude,
                'active_reactors_count' => $plant->active_reactors_count,
                'total_reactors_count' => $plant->reactors->count(),
                'latest_production_mw' => $plant->latest_production_mw,
                'total_production_mw' => $plant->total_production_mw,
                'percent_value' => $plant->percent_value,
            ])->toArray();
    }

    #[Computed]
    public function plants()
    {
        return Plant::query()
            ->with([
                'reactors:id,name,plant_id,stage,net_power_mw,reactor_index',
                'reactors.latestRecord.reactor:id,net_power_mw',
            ])
            ->whereHas('reactors')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
    }
}
