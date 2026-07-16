<?php

namespace App\Services;

use App\Models\Plant;
use App\Models\Record;
use Illuminate\Support\Carbon;

class ReactorSeries
{
    public static function between(Plant $plant, Carbon $start, Carbon $end): array
    {
        $key = "reactor_series_{$plant->id}_{$start->timestamp}_{$end->timestamp}";

        return cache()->remember($key, now()->addMinutes(10), function () use ($plant, $start, $end) {
            $reactors = $plant->reactors->sortBy('reactor_index')->values();

            $byReactor = Record::query()
                ->whereIn('reactor_id', $reactors->pluck('id'))
                ->whereBetween('date', [$start, $end])
                ->orderBy('date')
                ->get(['reactor_id', 'date', 'value'])
                ->groupBy('reactor_id');

            return $reactors->map(fn ($reactor) => [
                'name' => 'Tranche '.$reactor->reactor_index,
                'data' => ($byReactor[$reactor->id] ?? collect())
                    ->map(fn (Record $record) => [
                        'time' => (int) $record->date->timestamp,
                        'value' => (int) $record->value,
                    ])
                    // lightweight-charts requires strictly-ascending, unique timestamps.
                    ->keyBy('time')
                    ->sortKeys()
                    ->values()
                    ->all(),
            ])->values()->all();
        });
    }
}
