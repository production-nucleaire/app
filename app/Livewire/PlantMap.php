<?php

namespace App\Livewire;

use App\Models\Plant;
use App\Models\Reactor;
use App\Models\Record;
use App\Services\ReactorSeries;
use App\Support\Sparkline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PlantMap extends Component
{
    public int $selectedPlantId = 0;

    public int $selectedReactorId = 0;

    public int $previewPlantId = 0;

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

    /**
     * Open the sidebar preview for a plant (from a list row or a map marker). Nudges the
     * napping-plant easter egg when the plant is currently consuming.
     */
    public function openPreview(int $plantId): void
    {
        $this->previewPlantId = $plantId;

        $plant = $this->plants->firstWhere('id', $plantId);
        if ($plant && $plant->latest_production_mw < 0) {
            $this->dispatch('easter-nap', name: $plant->name);
        }
    }

    #[On('preview-plant')]
    public function previewFromMarker($plantId): void
    {
        $this->openPreview((int) $plantId);
    }

    #[Computed]
    public function previewPlant(): ?Plant
    {
        return $this->previewPlantId
            ? $this->plants->firstWhere('id', $this->previewPlantId)
            : null;
    }

    /** 24h production sparkline (SVG) for the previewed plant. */
    public function previewSpark(): string
    {
        $plant = $this->previewPlant();
        if (! $plant) {
            return '';
        }

        $points = Record::query()
            ->whereIn('reactor_id', $plant->reactors->pluck('id'))
            ->whereBetween('date', [now()->subHours(24), now()])
            ->orderBy('date')
            ->get(['date', 'value'])
            ->groupBy(fn (Record $r) => $r->date->format('Y-m-d H'))
            ->map(fn (Collection $g) => (int) $g->sum('value'))
            ->values()
            ->all();

        return Sparkline::render($points, 300, 60, '#0d8a4f', 'rgba(13,138,79,0.10)');
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

    /**
     * Earliest record timestamp (unix seconds) for the selected plant — the floor for the
     * chart's "load older on drag" so it stops requesting once no older data can exist.
     */
    #[Computed]
    public function reactorMinTime(): ?int
    {
        if (! $this->selectedPlant) {
            return null;
        }

        $min = Record::whereIn('reactor_id', $this->selectedPlant->reactors->pluck('id'))->min('date');

        return $min ? Carbon::parse($min)->timestamp : null;
    }

    public function render()
    {
        return view('livewire.plant-map');
    }
}
