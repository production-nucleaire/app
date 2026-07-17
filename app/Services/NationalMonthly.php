<?php

namespace App\Services;

use App\Models\Plant;
use Illuminate\Support\Carbon;

class NationalMonthly
{
    /**
     * National production synthesis grouped by year, for the history "Tableau" view.
     * Built from daily national totals (NationalSeries at daily granularity), then folded in
     * PHP into per-year synthesis rows + sub-rows at the requested granularity
     * ('jour' | 'semaine' | 'mois' | 'annee'). Cached ~30 min per (window, grain).
     *
     * Returns years newest-first; each year has newest-first `rows` (empty for the 'annee' grain,
     * where the year synthesis row is itself the leaf). Sub-row "vs période −1" deltas run across
     * the whole chronological stream (they carry over year boundaries, like the source design).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function table(Carbon $start, Carbon $end, string $grain = 'mois'): array
    {
        $grain = in_array($grain, ['jour', 'semaine', 'mois', 'annee'], true) ? $grain : 'mois';
        $key = "national_periods_{$grain}_{$start->timestamp}_{$end->timestamp}";

        return cache()->remember($key, now()->addMinutes(30), function () use ($start, $end, $grain) {
            $daily = NationalSeries::between($start, $end, 10); // [{time, value GW}]

            if (empty($daily)) {
                return [];
            }

            $capacityGw = self::capacityGw();
            $tz = config('app.timezone');

            // Normalise to ascending [ ['date' => Carbon (app tz), 'value' => GW], ... ]. Reading
            // the timestamp back in the app tz keeps each month/day in its own bucket (a UTC
            // read-back would shift the 1st-of-month into the previous month).
            $days = [];
            foreach ($daily as $point) {
                $days[] = ['date' => Carbon::createFromTimestamp($point['time'], $tz), 'value' => $point['value']];
            }

            // Group days by calendar year.
            $byYear = [];
            foreach ($days as $day) {
                $byYear[$day['date']->year][] = $day;
            }
            ksort($byYear);

            $prevAvg = null;      // carries across the whole stream, for sub-row deltas
            $prevYearFull = null; // last full-year TWh, for the year YoY delta
            $years = [];

            foreach ($byYear as $year => $yearDays) {
                $monthsPresent = [];
                foreach ($yearDays as $day) {
                    $monthsPresent[$day['date']->format('Y-m')] = true;
                }

                $rows = [];
                if ($grain !== 'annee') {
                    foreach (self::bucketize($yearDays, $grain) as $bucket) {
                        $values = $bucket['values'];
                        $avg = array_sum($values) / count($values);
                        // Skip the delta when the baseline is negligible — a % change from ~0 GW
                        // (a period with almost no data / a fleet near cold shutdown) is meaningless.
                        $delta = $prevAvg !== null && $prevAvg >= 1.0 ? ($avg - $prevAvg) / $prevAvg * 100 : null;
                        $prevAvg = $avg;

                        $rows[] = [
                            'label' => $bucket['label'],
                            'avg' => $avg,
                            'min' => min($values),
                            'max' => max($values),
                            'fdc' => $capacityGw > 0 ? (int) round($avg / $capacityGw * 100) : 0,
                            'twh' => array_sum($values) * 24 / 1000,
                            'deltaPct' => $delta,
                        ];
                    }
                }

                $yearValues = array_map(fn ($d) => $d['value'], $yearDays);
                $yearAvg = array_sum($yearValues) / count($yearValues);
                $yearTwh = array_sum($yearValues) * 24 / 1000;
                $isCurrent = $year === (int) now()->year;
                $isFull = count($monthsPresent) >= 12;

                $yearDelta = null;
                if ($isFull && ! $isCurrent && $prevYearFull !== null && $prevYearFull > 0) {
                    $yearDelta = ($yearTwh - $prevYearFull) / $prevYearFull * 100;
                }

                $years[] = [
                    'year' => $year,
                    'label' => (string) $year,
                    'badge' => self::badge($year, $isCurrent, $isFull),
                    'badgeType' => $year === 2022 ? 'danger' : 'muted',
                    'avg' => $yearAvg,
                    'min' => min($yearValues),
                    'max' => max($yearValues),
                    'fdc' => $capacityGw > 0 ? (int) round($yearAvg / $capacityGw * 100) : 0,
                    'twh' => $yearTwh,
                    'deltaPct' => $yearDelta,
                    'rows' => array_reverse($rows),
                ];

                if ($isFull && ! $isCurrent) {
                    $prevYearFull = $yearTwh;
                }
            }

            return array_reverse($years);
        });
    }

    /**
     * Split one year's daily points into ascending buckets at the requested grain.
     * Each bucket: ['label' => string, 'values' => float[]].
     *
     * @param  array<int,array{date:Carbon,value:float}>  $days
     * @return array<int,array{label:string,values:array<int,float>}>
     */
    protected static function bucketize(array $days, string $grain): array
    {
        $buckets = [];
        foreach ($days as $day) {
            [$sortKey, $label] = match ($grain) {
                'jour' => [$day['date']->format('Y-m-d'), $day['date']->translatedFormat('j F')],
                'semaine' => self::weekKeyLabel($day['date']),
                default => [$day['date']->format('Y-m'), $day['date']->translatedFormat('F Y')],
            };
            $buckets[$sortKey]['label'] ??= $label;
            $buckets[$sortKey]['values'][] = $day['value'];
        }
        ksort($buckets);

        return array_values($buckets);
    }

    /** ISO-week sort key + human label ("sem. 3 · 13 – 19 janv.") for a date. */
    protected static function weekKeyLabel(Carbon $date): array
    {
        $start = $date->copy()->startOfWeek();
        $end = $date->copy()->endOfWeek();
        $key = $date->isoWeekYear.'-W'.str_pad((string) $date->isoWeek, 2, '0', STR_PAD_LEFT);
        $label = 'sem. '.$date->isoWeek.' · '.$start->translatedFormat('j').' – '.$end->translatedFormat('j M');

        return [$key, $label];
    }

    /** Installed net capacity of the fleet, in GW. */
    protected static function capacityGw(): float
    {
        return Plant::query()->with('reactors:id,plant_id,net_power_mw')->get()
            ->sum(fn (Plant $p) => $p->reactors->sum('net_power_mw')) / 1000;
    }

    protected static function badge(int $year, bool $isCurrent, bool $isFull): string
    {
        return match (true) {
            $isCurrent => 'en cours',
            $year === 2022 => 'crise corrosion',
            ! $isFull => 'partiel',
            default => '12 mois',
        };
    }
}
