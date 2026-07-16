<?php

namespace App\Livewire;

use App\Models\Plant;
use App\Models\Record;
use App\Support\LoadColor;
use App\Support\Sparkline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class History extends Component
{
    #[Url(as: 'periode')]
    public string $range = '30 j';

    /**
     * Bucket granularity (substr length on the stored date) per range.
     */
    protected function ranges(): array
    {
        return [
            '24 h' => [
                'len' => 13,
                'start' => now()->subHours(24)
            ],
            '7 j' => [
                'len' => 13,
                'start' => now()->subDays(7)
            ],
            '30 j' => [
                'len' => 10,
                'start' => now()->subDays(30)
            ],
            '12 mois' => [
                'len' => 7,
                'start' => now()->subMonths(12)
            ],
            '5 ans' => [
                'len' => 7,
                'start' => now()->subYears(5)
            ],
            'Max' => [
                'len' => 7,
                'start' => null
            ],
        ];
    }

    public function rangeKeys(): array
    {
        return array_keys($this->ranges());
    }

    public function updatedRange(): void
    {
        // wire:ignore keeps the chart div, so push fresh points to the client JS.
        $this->dispatch('history-updated', points: $this->points());
    }

    protected function config(): array
    {
        $ranges = $this->ranges();
        $cfg = $ranges[$this->range] ?? $ranges['30 j'];
        $cfg['start'] = $cfg['start'] ?? (Record::min('date')
            ? Carbon::parse(Record::min('date'))
            : now()->subYears(2));

        return $cfg;
    }

    /**
     * National production series (GW) for the selected range. Averages each reactor within
     * a bucket first (a subquery — dedupes the occasional multiple readings per hour) then
     * sums across reactors, giving the correct national mean per hour/day/month in one query.
     *
     * @return array<int,array{time:int,value:float}>
     */
    #[Computed]
    public function points(): array
    {
        ['len' => $len, 'start' => $start] = $this->config();

        return cache()->remember("history_points_{$this->range}", now()->addMinutes(15), function () use ($len, $start) {
            $perReactor = DB::table('records')
                ->where('date', '>=', $start)
                ->selectRaw('substr(date, 1, '.$len.') as bucket, reactor_id, avg(value) as ravg')
                ->groupBy('bucket', 'reactor_id');

            $rows = DB::query()
                ->fromSub($perReactor, 't')
                ->selectRaw('bucket, sum(ravg) as total')
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get();

            return $rows->map(fn ($row) => [
                'time' => $this->bucketTimestamp($row->bucket, $len),
                'value' => round($row->total / 1000, 2),
            ])->values()->all();
        });
    }

    /**
     * Parse a substr bucket key ('Y-m-d H' | 'Y-m-d' | 'Y-m') to a unix timestamp.
     */
    protected function bucketTimestamp(string $bucket, int $len): int
    {
        return match ($len) {
            13 => Carbon::createFromFormat('Y-m-d H', $bucket)->timestamp,
            10 => Carbon::createFromFormat('Y-m-d', $bucket)->startOfDay()->timestamp,
            default => Carbon::createFromFormat('Y-m', $bucket)->startOfMonth()->timestamp,
        };
    }

    /**
     * Summary tiles: mean / min / max GW and mean load factor over the period.
     */
    #[Computed]
    public function stats(): array
    {
        $values = array_column($this->points(), 'value');

        if (empty($values)) {
            return [
                'avg' => 0,
                'min' => 0,
                'max' => 0,
                'fdc' => 0
            ];
        }

        $avg = array_sum($values) / count($values);
        $capacityGw = (int) Plant::query()->with('reactors')->get()
            ->sum(fn ($p) => $p->reactors->sum('net_power_mw')) / 1000;

        return [
            'avg' => $avg,
            'min' => min($values),
            'max' => max($values),
            'fdc' => $capacityGw > 0 ? round($avg / $capacityGw * 100) : 0,
        ];
    }

    /**
     * Per-plant small multiples: a shape sparkline over the period + current load label.
     *
     * @return array<int,array{name:string,pct:int,color:string,spark:string}>
     */
    #[Computed]
    public function minis(): array
    {
        ['len' => $len, 'start' => $start] = $this->config();

        $buckets = cache()->remember("history_minis_{$this->range}", now()->addMinutes(15), function () use ($len, $start) {
            // Average per reactor per bucket (dedupe), then sum per plant per bucket.
            $perReactor = DB::table('records')
                ->join('reactors', 'records.reactor_id', '=', 'reactors.id')
                ->where('records.date', '>=', $start)
                ->selectRaw('reactors.plant_id as pid, reactors.id as rid, substr(records.date, 1, '.$len.') as bucket, avg(records.value) as ravg')
                ->groupBy('pid', 'rid', 'bucket');

            return DB::query()
                ->fromSub($perReactor, 't')
                ->selectRaw('pid, bucket, sum(ravg) as total')
                ->groupBy('pid', 'bucket')
                ->orderBy('bucket')
                ->get()
                ->groupBy('pid');
        });

        return Plant::query()
            ->with('reactors.latestRecord')
            ->whereHas('reactors')
            ->get()
            ->sortByDesc->latest_production_mw
            ->map(function (Plant $plant) use ($buckets) {
                $pct = (int) round($plant->percent_value);
                $mw = $plant->latest_production_mw;
                $totals = ($buckets[$plant->id] ?? collect())->pluck('total')->map(fn ($t) => (int) $t);
                $totals = $this->downsample($totals->all(), 40);

                $token = LoadColor::token($mw, $pct);
                $hex = $this->tokenHex($token);

                return [
                    'name' => $plant->name,
                    'pct' => $pct,
                    'color' => 'var('.$token.')',
                    'spark' => Sparkline::render($totals, 190, 42, $hex, 'rgba(18,74,99,0.06)'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Reduce a series to at most $max evenly-spaced points for a compact sparkline.
     */
    protected function downsample(array $points, int $max): array
    {
        $n = count($points);
        if ($n <= $max) {
            return $points;
        }

        $step = $n / $max;
        $out = [];
        for ($i = 0; $i < $max; $i++) {
            $out[] = $points[(int) floor($i * $step)];
        }

        return $out;
    }

    /**
     * Sparkline SVG needs a concrete stroke color, so resolve the token to a light-mode hex.
     */
    protected function tokenHex(string $token): string
    {
        return match ($token) {
            '--color-danger' => '#b5471d',
            '--color-accent' => '#0d8a4f',
            '--color-accent-mid' => '#5fb98a',
            default => '#9aa4a9',
        };
    }

    public function render()
    {
        return view('livewire.history');
    }
}
