<?php

namespace App\View\Components;

use Closure;
use App\Models\Reactor;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class ReactorProductionChart extends Component
{
    public Reactor $reactor;

    public array $records = [];

    public Carbon $day;

    public ?Carbon $previousDay = null;
    public ?Carbon $nextDay = null;

    /**
     * Create a new component instance.
     */
    public function __construct(Reactor $reactor, ?string $day = null)
    {
        $this->day = $day ? Carbon::parse($day) : now();
        $this->previousDay = $this->day->copy()->subDay();
        $this->nextDay = $this->day->lt(today()) ? $this->day->copy()->addDay() : null;

        $this->reactor = $reactor;

        $this->records = $reactor->records
            ->whereBetween('date', [
                $this->day->copy()->startOfDay(),
                $this->day->copy()->endOfDay()
            ])
            ->sortBy('date')
            ->map(function ($record) {
                return [
                    'date' => $record->date->format('Y-m-d H:i:s'),
                    'time' => $record->date->format('H:i'),
                    'value' => $record->value,
                    'percent_value' => $record->percent_value,
                ];
            })->toArray();

            // Double the first hour to ensure the chart start with a full hour
            // This is to avoid the chart starting at 00:00 with no data visible.
            if (1 === count($this->records)) {
                $datetime = Carbon::parse(end($this->records)['date']);
                if (0 === $datetime->hour) {
                    $this->records = [
                        end($this->records),
                        [
                            ...end($this->records),
                            'date' => $datetime->addHour()->format('Y-m-d H:i:s'),
                        ],
                    ];
                }
            }
    }

    public function chart(): string
    {
        $width = 320;
        $height = 200;
        $padding = 50;
        $chartWidth = $width - 2 * $padding;
        $chartHeight = $height - 2 * $padding;

        // Normalize data to 24 hourly slots
        $data = array_fill(0, 24, null);
        foreach ($this->records as $record) {
            $hour = Carbon::parse($record['date'])->hour;
            $data[$hour] = (int) $record['value'];
        }

        // Fill missing hours (interpolate or set to 0)
        $data = array_map(fn($v) => $v ?? 0, $data);

        // Scale function
        $scaleX = fn($i) => $padding + ($i / 23) * $chartWidth;
        $scaleY = fn($v) => $padding + $chartHeight * (1 - ($v / $this->reactor->net_power_mw));

        // Determine the last non-zero index
        $lastNonZeroIndex = 0;
        foreach ($data as $i => $v) {
            if ($v > 0) {
                $lastNonZeroIndex = $i;
            }
        }

        // Build points for path
        $points = [];
        foreach ($data as $i => $v) {
            if ($i > $lastNonZeroIndex) {
                break; // stop at the last non-zero point
            }

            $points[] = [$scaleX($i), $scaleY($v)];
        }

        // Smoothing (simple Bézier smoothing)
        $path = "M {$points[0][0]},{$points[0][1]} ";
        for ($i = 1; $i < count($points) - 1; $i++) {
            $xc = ($points[$i][0] + $points[$i + 1][0]) / 2;
            $yc = ($points[$i][1] + $points[$i + 1][1]) / 2;
            $path .= "Q {$points[$i][0]},{$points[$i][1]} $xc,$yc ";
        }

        // Close area
        $lastX = $points[count($points) - 1][0];
        $path .= "L $lastX,$height L {$points[0][0]},$height Z";

        $xLabels = [
            ['hour' => 6, 'label' => '06:00'],
            ['hour' => 12, 'label' => '12:00'],
            ['hour' => 18, 'label' => '18:00'],
        ];

        $xTicks = '';
        foreach ($xLabels as $label) {
            $x = $scaleX($label['hour']);
            $innerText = $label['label'];
            $y = $height + 15; // Position below the chart
            $xTicks .= <<<SVG
                <text x="$x" y="$y" text-anchor="middle" font-size="12" fill="#444">$innerText</text>
            SVG;
        }

        $yTicks = '';
        $divisions = 4;
        $step = $this->reactor->net_power_mw / $divisions;

        for ($i = 0; $i <= $divisions; $i++) {
            $value = $i * $step;
            $x = $padding - 10; // Position to the left of the chart
            $y = $scaleY($value) - ($i * 10) + 40;
            $label = $i === $divisions
                ? round($this->reactor->net_power_mw)
                : round($value);
            $yTicks .= <<<SVG
                <text x="{$x}" y="{$y}" text-anchor="end" alignment-baseline="middle" font-size="12" fill="#444">{$label}</text>
            SVG;
        }

        // Time areas
        $hours = '';
        foreach ($points as $i => [$x, $y]) {
            $value = $data[$i];
            $time = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';

            // Determine hover region width
            $x1 = $x - ($chartWidth / 48); // Half of 1/24th of the chart
            $areaWidth = $chartWidth / 24;

            $record = $this->records[$i] ?? null;
            $percentValue = $record['percent_value'] ?? 0;

            $hours .= <<<SVG
                <rect
                    x="$x1"
                    y="0"
                    width="$areaWidth"
                    height="$height"
                    fill="transparent"
                    x-on:mouseover="selectedRecord = { time: '$time', value: $value, percent_value: '$percentValue' }"
                />
            SVG;
        }

        $lines = [
            [
                'x1' => $padding,
                'y1' => $padding,
                'x2' => $padding,
                'y2' => $height,
                'stroke' => '#ccc',
            ],
            [
                'x1' => $padding,
                'y1' => $height,
                'x2' => $width - 40,
                'y2' => $height,
                'stroke' => '#ccc',
            ],
        ];

        // Output SVG
        return <<<SVG
            <svg viewBox="0 0 320 240" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" class="w-80 h-48 mb-4">
                <linearGradient id="gradient" gradientUnits="userSpaceOnUse" x1="256" x2="256" y1="407" y2="105">
                    <stop offset="0" stop-color="#00c950"></stop>
                    <stop offset="1" stop-color="#05df72"></stop>
                </linearGradient>
                <!-- Area -->
                <path id="area-chart" d="$path" fill="url(#gradient)" stroke="none"/>
                <!-- Line -->
                <path id="line-chart" d="M {$points[0][0]},{$points[0][1]} " 
                    stroke="#057f34" stroke-width="2" fill="none"
                    d="$path" />
                <!-- Time areas -->
                $hours
                <!-- Axes -->
                <line x1="{$lines[0]['x1']}" y1="{$lines[0]['y1']}" x2="{$lines[0]['x2']}" y2="{$lines[0]['y2']}" stroke="#ccc"/>
                <line x1="{$lines[1]['x1']}" y1="{$lines[1]['y1']}" x2="{$lines[1]['x2']}" y2="{$lines[1]['y2']}" stroke="#ccc"/>
                <!-- Axis Labels -->
                $xTicks
                $yTicks
            </svg>
        SVG;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.reactor-production-chart', [
            'chart' => $this->chart(),
        ]);
    }
}
