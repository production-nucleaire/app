<?php

namespace App\Livewire;

use App\Models\Plant;
use App\Models\Reactor;
use App\Services\ReactorSeries;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PlantMap extends Component
{
    public int $selectedPlantId = 0;

    public int $selectedReactorId = 0;

    public ?Plant $previousPlant = null;

    public ?Plant $nextPlant = null;

    public ?Plant $selectedPlant = null;

    public ?Reactor $selectedReactor = null;

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
            $this->selectedReactor?->setRelation('plant', $this->selectedPlant);
        }
    }

    public function setNavigation()
    {
        $index = $this->plants->search(fn ($p) => $p->id === $this->selectedPlantId);
        $this->previousPlant = $this->plants->get($index - 1);
        $this->nextPlant = $this->plants->get($index + 1);
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
                'reactors:id,name,plant_id,stage,net_power_mw,reactor_index,grid_link_date',
                'reactors.latestRecord.reactor:id,net_power_mw',
            ])
            ->whereHas('reactors')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
    }

    #[Computed]
    public function reactorSeries()
    {
        if (! $this->selectedPlant) {
            return [];
        }

        return ReactorSeries::between(
            $this->selectedPlant,
            now()->subDays(30),
            now(),
        );
    }

    public function render()
    {
        return view('livewire.plant-map');
    }
}
