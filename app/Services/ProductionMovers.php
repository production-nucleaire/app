<?php

namespace App\Services;

use App\Models\Plant;
use Illuminate\Support\Collection;

/**
 * Ranks plants by how much their total production changed over the last hour.
 *
 * Mirrors the hour-bucketing idiom used by NationalStats::spark24h() /
 * ShareImageService::plant24h() and the last-vs-previous delta of
 * ShareImageService::hourlyDeltaGw(), applied per plant.
 */
class ProductionMovers
{
    /**
     * The top $limit plants by absolute MW change between the last two hourly
     * buckets. Plants without two buckets or with a zero delta are dropped.
     *
     * @return array<int,array{plant: Plant, delta: int, current: int}>
     */
    public static function top(int $limit = 2): array
    {
        return Plant::query()
            ->whereHas('reactors')
            ->get()
            ->map(function (Plant $plant) {
                $buckets = $plant->records()
                    ->whereBetween('date', [now()->subHours(2), now()])
                    ->orderBy('date')
                    ->get(['date', 'value'])
                    ->groupBy(fn ($r) => $r->date->format('Y-m-d H'))
                    ->map(fn (Collection $g) => (int) $g->sum('value'))
                    ->values();

                if ($buckets->count() < 2) {
                    return null;
                }

                $current = (int) $buckets->last();
                $delta = $current - (int) $buckets[$buckets->count() - 2];

                return ['plant' => $plant, 'delta' => $delta, 'current' => $current];
            })
            ->filter(fn (?array $m) => $m !== null && $m['delta'] !== 0)
            ->sortByDesc(fn (array $m) => abs($m['delta']))
            ->take($limit)
            ->values()
            ->all();
    }
}
