<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NationalSeries
{
    /**
     * National production series (GW) between two dates at a given bucket granularity
     * (substr length: 13 = hourly, 10 = daily, 7 = monthly). Averages each reactor within a
     * bucket first (subquery — dedupes multiple readings per hour) then sums across reactors.
     * Cached ~10 min; historical windows are immutable. Shared by the Historique component and
     * the /api/history "load older" endpoint.
     *
     * @return array<int,array{time:int,value:float}>
     */
    public static function between(Carbon $start, Carbon $end, int $len): array
    {
        $len = in_array($len, [13, 10, 7], true) ? $len : 10;
        $key = "national_series_{$len}_{$start->timestamp}_{$end->timestamp}";

        return cache()->remember($key, now()->addMinutes(10), function () use ($start, $end, $len) {
            $perReactor = DB::table('records')
                ->where('date', '>=', $start)
                ->where('date', '<=', $end)
                ->selectRaw('substr(date, 1, '.$len.') as bucket, reactor_id, avg(value) as ravg')
                ->groupBy('bucket', 'reactor_id');

            return DB::query()
                ->fromSub($perReactor, 't')
                ->selectRaw('bucket, sum(ravg) as total')
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get()
                ->map(fn ($row) => [
                    'time' => self::bucketTimestamp($row->bucket, $len),
                    'value' => round($row->total / 1000, 2),
                ])
                ->values()
                ->all();
        });
    }

    /** Parse a substr bucket key ('Y-m-d H' | 'Y-m-d' | 'Y-m') to a unix timestamp. */
    protected static function bucketTimestamp(string $bucket, int $len): int
    {
        return match ($len) {
            13 => Carbon::createFromFormat('Y-m-d H', $bucket)->timestamp,
            10 => Carbon::createFromFormat('Y-m-d', $bucket)->startOfDay()->timestamp,
            default => Carbon::createFromFormat('Y-m', $bucket)->startOfMonth()->timestamp,
        };
    }
}
