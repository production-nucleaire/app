<?php

namespace App\Livewire;

use App\Models\Plant;
use App\Models\Reactor;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;

class PlantMap extends Component
{
    #[Url(as: 'plant')]
    public int $selectedPlantId = 0;

    #[Url(as: 'reactor')]
    public int $selectedReactorId = 0;

    #[Url(as: 'day')]
    public ?string $day = null;

    public ?Plant $previousPlant = null;
    public ?Plant $nextPlant = null;

    public ?Plant $selectedPlant = null;
    public ?Reactor $selectedReactor = null;

    public function mount()
    {
        if ($this->selectedPlantId) {
            $this->selectedPlant = Plant::find($this->selectedPlantId);

            $this->setNavigation();
        }

        if ($this->selectedReactorId) {
            $this->selectedReactor = Reactor::find($this->selectedReactorId);
        }
    }

    public function setNavigation()
    {
        $index = $this->plants->search(fn ($plant) => $plant->id === $this->selectedPlantId);
        $this->previousPlant = $this->plants->get($index - 1) ?? null;
        $this->nextPlant = $this->plants->get($index + 1) ?? null;
    }

    public function updatedSelectedPlantId($value)
    {
        $this->selectedPlant = Plant::find($value);
        $this->setNavigation();

        $this->dispatch('plant-selected', ['plantId' => $this->selectedPlantId]);
    }

    public function updatedSelectedReactorId($value)
    {
        $this->selectedReactor = Reactor::find($value);
    }

    #[On('select-plant')]
    public function selectPlant($plantId)
    {
        $this->selectedPlantId = $plantId;
        $this->selectedPlant = Plant::find($plantId);
        $this->setNavigation();

        $this->dispatch('plant-selected', ['plantId' => $plantId]);
    }

    #[Computed]
    public function markers()
    {
        return $this->plants
            ->map(fn ($plant) => [
                'id' => $plant->id,
                'name' => $plant->name,
                'lat' => $plant->latitude,
                'lng' => $plant->longitude,
                'active_reactors' => $plant->reactors->filter(function ($reactor) {
                    $record = $reactor->records()
                        ->where('date', '>=', now()->subHour())
                        ->latest('date')
                        ->first();
                    return $record && $record->percent_value >= 5;
                })->count(),
                'total_reactors' => $plant->reactors->count(),
            ])->toArray();
    }

    #[Computed]
    public function plants()
    {
        return Plant::query()
            ->with(['reactors' => function ($query) {
                $query->select('id', 'plant_id', 'net_power_mw', 'reactor_index');
            }])
            ->select('id', 'name', 'latitude', 'longitude')
            ->whereHas('reactors')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
    }
}
