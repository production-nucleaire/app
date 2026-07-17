<?php

namespace App\Livewire;

use App\Models\Plant;
use App\Models\Record;
use App\Services\NationalMonthly;
use App\Services\NationalSeries;
use App\Support\LoadColor;
use App\Support\Sparkline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class History extends Component
{
    #[Url(as: 'periode')]
    public string $range = '30 j';

    #[Url(as: 'du')]
    public ?string $from = null;

    #[Url(as: 'au')]
    public ?string $to = null;

    #[Url(as: 'vue')]
    public string $view = 'graphe'; // graphe | tableau

    #[Url(as: 'grain')]
    public string $grain = 'mois'; // jour | semaine | mois | annee

    public function setView(string $view): void
    {
        $this->view = $view === 'tableau' ? 'tableau' : 'graphe';
    }

    public function setGrain(string $grain): void
    {
        $this->grain = in_array($grain, ['jour', 'semaine', 'mois', 'annee'], true) ? $grain : 'mois';
    }

    /** Granularity options for the table sub-toolbar (key => label). */
    public function grains(): array
    {
        return ['jour' => 'Jour', 'semaine' => 'Semaine', 'mois' => 'Mois', 'annee' => 'Année'];
    }

    /** Full data span, shown next to the granularity selector, e.g. "déc. 2024 → juil. 2026". */
    public function periodLabel(): string
    {
        $min = Record::min('date');
        $max = Record::max('date');

        if (! $min || ! $max) {
            return '';
        }

        return Carbon::parse($min)->translatedFormat('M Y').' → '.Carbon::parse($max)->translatedFormat('M Y');
    }

    /**
     * Bucket granularity (substr length on the stored date) per range.
     */
    protected function ranges(): array
    {
        return [
            '24 h' => [
                'len' => 13,
                'start' => now()->subHours(24),
            ],
            '7 j' => [
                'len' => 13,
                'start' => now()->subDays(7),
            ],
            '30 j' => [
                'len' => 10,
                'start' => now()->subDays(30),
            ],
            '12 mois' => [
                'len' => 7,
                'start' => now()->subMonths(12),
            ],
            '5 ans' => [
                'len' => 7,
                'start' => now()->subYears(5),
            ],
            'Max' => [
                'len' => 7,
                'start' => null,
            ],
        ];
    }

    public function rangeKeys(): array
    {
        return array_keys($this->ranges());
    }

    /** Activate a preset and drop any custom range (they are mutually exclusive). */
    public function selectRange(string $key): void
    {
        $this->range = $key;
        $this->from = null;
        $this->to = null;
        $this->pushChart();
    }

    public function updated($property): void
    {
        // wire:ignore keeps the chart div, so push fresh points to the client JS.
        // 'view' is included so returning to the graph re-mounts the chart at the correct size.
        if (in_array($property, ['range', 'from', 'to', 'view'], true)) {
            $this->pushChart();
        }
    }

    /** Re-hydrate the wire:ignore chart with the current window's data + lazy-load metadata. */
    protected function pushChart(): void
    {
        $this->dispatch('history-updated',
            points: $this->points(),
            len: $this->currentLen(),
            minTime: $this->minTime(),
        );
    }

    /**
     * Resolve the active window: a custom du/au range (auto granularity by span) takes
     * precedence over the selected preset. Returns len (substr bucket size), start and end.
     */
    protected function config(): array
    {
        if ($this->from && $this->to) {
            $start = Carbon::parse($this->from)->startOfDay();
            $end = Carbon::parse($this->to)->endOfDay();
            if ($end->lessThan($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            $days = $start->diffInDays($end);
            $len = $days <= 2 ? 13 : ($days <= 60 ? 10 : 7);

            return ['len' => $len, 'start' => $start, 'end' => $end];
        }

        $ranges = $this->ranges();
        $cfg = $ranges[$this->range] ?? $ranges['30 j'];
        $cfg['start'] = $cfg['start'] ?? (Record::min('date')
            ? Carbon::parse(Record::min('date'))
            : now()->subYears(2));
        $cfg['end'] = now();

        return $cfg;
    }

    /** Cache suffix that varies with the active window (preset or custom du/au). */
    protected function cacheKey(string $prefix): string
    {
        return $prefix.'_'.$this->range.'_'.($this->from ?? '').'_'.($this->to ?? '');
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
        ['len' => $len, 'start' => $start, 'end' => $end] = $this->config();

        return NationalSeries::between($start, $end, $len);
    }

    /** Bucket granularity of the active window — the client fetches "load older" at this len. */
    public function currentLen(): int
    {
        return $this->config()['len'];
    }

    /** Earliest national record timestamp (unix seconds) — floor for the chart's load-older. */
    public function minTime(): ?int
    {
        $min = Record::min('date');

        return $min ? Carbon::parse($min)->timestamp : null;
    }

    /**
     * The full data span — the table always shows everything and is sliced by granularity,
     * so the range presets / du-au picker (which drive the chart) don't apply here.
     *
     * @return array{start:Carbon,end:Carbon}
     */
    protected function tableWindow(): array
    {
        $min = Record::min('date');

        return [
            'start' => $min ? Carbon::parse($min) : now()->subYear(),
            'end' => now(),
        ];
    }

    /**
     * Production synthesis grouped by year at the selected granularity (the "Tableau" view).
     *
     * @return array<int,array<string,mixed>>
     */
    #[Computed]
    public function monthlyTable(): array
    {
        ['start' => $start, 'end' => $end] = $this->tableWindow();

        return NationalMonthly::table($start, $end, $this->grain);
    }

    /** Stream the current-granularity synthesis as a CSV (year totals + sub-rows, newest first). */
    public function exportCsv(): StreamedResponse
    {
        $filename = 'production-nucleaire-'.$this->grain.'-'.Carbon::now()->format('Y-m-d-Hi').'.csv';
        $years = $this->monthlyTable();
        $fr = fn (float $v, int $d = 1) => number_format($v, $d, ',', '');

        $line = fn ($out, string $annee, string $periode, array $r) => fputcsv($out, [
            $annee,
            $periode,
            $fr($r['avg']),
            $fr($r['min']),
            $fr($r['max']),
            $r['fdc'],
            $fr($r['twh'], 2),
            $r['deltaPct'] !== null ? $fr($r['deltaPct']) : '',
        ], ';', '"', '');

        return response()->streamDownload(function () use ($years, $line) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}"); // BOM so Excel reads UTF-8
            fputcsv($out, ['Année', 'Période', 'Moyenne (GW)', 'Min (GW)', 'Max (GW)', 'Facteur de charge (%)', 'Énergie (TWh)', 'Évolution (%)'], ';', '"', '');

            foreach ($years as $year) {
                $line($out, $year['label'], $year['label'], $year); // year synthesis
                foreach ($year['rows'] as $row) {
                    $line($out, $year['label'], $row['label'], $row);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
                'fdc' => 0,
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
        ['len' => $len, 'start' => $start, 'end' => $end] = $this->config();

        $buckets = cache()->remember($this->cacheKey('history_minis'), now()->addMinutes(15), function () use ($len, $start, $end) {
            // Average per reactor per bucket (dedupe), then sum per plant per bucket.
            $perReactor = DB::table('records')
                ->join('reactors', 'records.reactor_id', '=', 'reactors.id')
                ->where('records.date', '>=', $start)
                ->where('records.date', '<=', $end)
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
                    'spark' => Sparkline::render($totals, 190, 42, $hex, 'rgba(18,74,99,0.06)', dot: false),
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
