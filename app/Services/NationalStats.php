<?php

namespace App\Services;

use App\Models\Plant;
use App\Models\Reactor;
use App\Models\Record;
use Illuminate\Support\Collection;

class NationalStats
{
    public static function get(): array
    {
        return cache()->remember('national:stats', now()->addMinutes(5), function () {
            $reactors = Reactor::with('latestRecord')->get();

            $capacity = (int) $reactors->sum('net_power_mw');
            $injected = (int) $reactors->sum(fn (Reactor $r) => $r->latestRecord?->value ?? 0);

            $coupled = $reactors->filter(function (Reactor $r) {
                $value = $r->latestRecord?->value;
                if ($value === null || $r->net_power_mw <= 0) {
                    return false;
                }

                return ($value / $r->net_power_mw) * 100 >= 5;
            })->count();

            return [
                'injected_mw' => $injected,
                'injected_gw' => round($injected / 1000, 1),
                'coupled' => $coupled,
                'total_reactors' => $reactors->count(),
                'capacity_mw' => $capacity,
                'load_factor_pct' => $capacity > 0 ? (int) round($injected / $capacity * 100) : 0,
                'top_plant_slug' => self::topPlantSlug(),
                'spark24h' => self::spark24h(),
            ];
        });
    }

    protected static function topPlantSlug(): ?string
    {
        return Plant::query()
            ->with(['reactors.latestRecord'])
            ->whereHas('reactors')
            ->get()
            ->sortByDesc(fn (Plant $p) => $p->latest_production_mw)
            ->first()?->slug;
    }

    protected static function spark24h(): array
    {
        return Record::query()
            ->whereBetween('date', [now()->subHours(24), now()])
            ->orderBy('date')
            ->get(['date', 'value'])
            ->groupBy(fn (Record $r) => $r->date->format('Y-m-d H'))
            ->map(fn (Collection $group) => (int) $group->sum('value'))
            ->values()
            ->all();
    }
}
