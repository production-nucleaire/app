<?php

namespace App\View\Components;

use Closure;
use App\Models\Reactor;
use Maantje\Charts\Chart;
use Maantje\Charts\XAxis;
use Maantje\Charts\YAxis;
use Maantje\Charts\Line\Line;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\View\Component;
use Maantje\Charts\Line\Lines;
use Maantje\Charts\Line\Point;
use Illuminate\Contracts\View\View;
use Maantje\Charts\Annotations\XAxis\XAxisLineAnnotation;

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
        // Normalize data to 24 hourly slots
        $data = array_fill(0, 24, null);
        foreach ($this->records as $record) {
            $hour = Carbon::parse($record['date'])->hour;
            $data[$hour] = (int) $record['value'];
        }

        // Fill missing hours (interpolate or set to 0)
        $data = array_map(fn($v) => $v ?? 0, $data);

        $steps = [];
        $last = 0;
        while ($last <= max($data)) {
            $steps[] = $last + 250;
            $last += 250;
        }

        if (empty($steps)) {
            $steps = [0, 250, 500, 750, 1000, 1250, 1500, 1750];
        }

        $chart = new Chart(
            width: 320,
            height: 200,
            leftMargin: 0,
            rightMargin: 20,
            // bottomMargin: 10,
            class: 'reactor-production-chart',
            xAxis: new XAxis(
                data: [0, 12, 23],
                formatter: fn ($label) => sprintf('%02d:00', $label),
                annotations: array_map(function ($i, $value) {
                    return new XAxisLineAnnotation(
                        x: $i,
                        color: '#ccc',
                        size: 1,
                        label: sprintf('À %02d:00 : %d MW', $i, $value),
                        id: "rector-production-chart__annotation-$i",
                        class: "rector-production-chart__annotation rector-production-chart__annotation-$i",
                    );
                }, array_keys($data), $data),
            ),
            yAxis: new YAxis(
                formatter: fn ($label) => Number::format($label, locale: 'fr'),
                minValue: 0,
                maxValue: max($steps),
            ),
            series: [
                new Lines(
                    lines: [
                        new Line(
                            id: 'line-a',
                            class: 'line-a',
                            points: array_map(
                                fn($i, $v) => new Point(x: $i, y: max($v ?? 0, 0)),
                                array_keys($data),
                                $data
                            ),
                            color: '#00c950',
                            areaColor: '#00c950',
                            curve: 100,
                            size: 1,
                        ),
                    ],
                ),
            ]
        );

        return $chart->render();
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
