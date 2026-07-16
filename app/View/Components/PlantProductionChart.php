<?php

namespace App\View\Components;

use App\Models\Plant;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class PlantProductionChart extends Component
{
    public Plant $plant;

    public bool $static = true;

    public array $records = [];

    public int $maxProduction = 0;

    public int $width;

    public int $height;

    public int $padding;

    public ?Carbon $previousDay = null;

    public ?Carbon $nextDay = null;

    /**
     * Create a new component instance.
     */
    public function __construct(
        int|Plant $plant,
        bool $static = true,
        int $width = 180,
        int $height = 60,
        int $padding = 10
    ) {
        $this->plant = $plant;

        $this->static = $static;

        $this->width = $width;
        $this->height = $height;
        $this->padding = $padding;
    }

    /**
     * Load and aggregate the plant's records for the last 24 hours.
     *
     * Kept out of the constructor so it only runs on a cache miss (see render()).
     */
    protected function loadRecords(): array
    {
        return $this->plant->records()
            ->whereBetween('date', [
                now()->copy()->subHours(24),
                now(),
            ])
            ->orderBy('date')
            ->get()
            ->groupBy(fn ($record) => $record->date->format('Y-m-d H:i'))
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'date' => $first->date->format('d/m/Y H:i:s'),
                    'time' => $first->date->format('H:i'),
                    'value' => $group->sum('value'),
                ];
            })
            ->values()
            ->toArray();
    }

    public function generateChart(): string
    {
        $width = $this->width;
        $height = $this->height;
        $padding = $this->padding;

        $this->records = $this->loadRecords();
        $this->maxProduction = (int) ($this->plant->reactors->sum('net_power_mw') ?? 0);

        $paths = '';

        $count = count($this->records);
        if ($count < 2 || $this->maxProduction <= 0) {
            $path = '';
        } else {
            // Calculate points
            $points = [];
            foreach ($this->records as $i => $record) {
                $x = $padding + ($i / ($count - 1)) * ($width - 2 * $padding);
                $y = $padding + (1 - min($record['value'] / $this->maxProduction, 1)) * ($height - 2 * $padding);
                $points[] = [$x, $y];
            }

            // Build smooth path using cubic Bézier curves
            $linePath = 'M '.implode(',', $points[0]);
            $areaPath = 'M '.implode(',', $points[0]);

            for ($i = 1; $i < count($points); $i++) {
                $p0 = $points[$i - 1];
                $p1 = $points[$i];
                $cx = ($p0[0] + $p1[0]) / 2;
                $cy = ($p0[1] + $p1[1]) / 2;

                $linePath .= ' Q '.implode(',', $p0).' '.implode(',', [$cx, $cy]);
                $areaPath .= ' Q '.implode(',', $p0).' '.implode(',', [$cx, $cy]);
            }

            // Close the area path (down to x of last point, then bottom, then back to x of first)
            $lastX = $points[$count - 1][0];
            $firstX = $points[0][0];
            $bottomY = $height - $padding;

            $areaPath .= " L {$lastX},{$bottomY} L {$firstX},{$bottomY} Z";

            $paths .= '<path d="'.$areaPath.'" class="fill-green-400" stroke="none" />';
            $paths .= '<path d="'.$linePath.'" class="fill-transparent stroke-green-700 stroke-2" />';

        }

        $svg = <<<SVG
            <svg viewBox="0 0 $width $height" xmlns="http://www.w3.org/2000/svg">
                $paths
            </svg>
        SVG;

        return $svg;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $key = 'plant_production_chart_'.$this->plant->id.'_'.$this->width.'x'.$this->height.'_p'.$this->padding;
        $chart = cache()->remember($key, now()->addMinutes(10), function () {
            return $this->generateChart();
        });

        return view('components.plant-production-chart', [
            'chart' => $chart,
        ]);
    }
}
