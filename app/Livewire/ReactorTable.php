<?php

namespace App\Livewire;

use App\Models\Plant;
use App\Support\LoadColor;
use App\Support\ReactorStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReactorTable extends Component
{
    #[Url(as: 'filtre')]
    public string $filter = 'toutes'; // toutes | couplees | arret

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'tri')]
    public string $sort = 'production'; // production | nom | charge

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['toutes', 'couplees', 'arret'], true) ? $filter : 'toutes';
    }

    public function setSort(string $sort): void
    {
        $this->sort = in_array($sort, ['production', 'nom', 'charge'], true) ? $sort : 'production';
    }

    /**
     * Fleet loaded with the same narrow eager-load as the map, sorted by the active sort key.
     * Cached for the lifetime of the request so counts + groups + export share one query.
     */
    protected function fleet(): Collection
    {
        return once(fn () => Plant::query()
            ->with([
                'reactors:id,name,plant_id,stage,net_power_mw,reactor_index,grid_link_date',
                'reactors.latestRecord.reactor:id,net_power_mw',
            ])
            ->whereHas('reactors')
            ->get());
    }

    /**
     * Per-reactor 24 h delta (MW), keyed by reactor id. Baseline = the latest reading in the
     * [-25 h, -23 h] window; diffed against the current latest value. Cached ~10 min.
     *
     * @return array<int,int>
     */
    protected function trends(): array
    {
        return cache()->remember('reactor_table_trends', now()->addMinutes(10), function () {
            $since = now()->subHours(25);
            $until = now()->subHours(23);

            $baseline = DB::table('records')
                ->whereBetween('date', [$since, $until])
                ->orderBy('date')
                ->get(['reactor_id', 'value'])
                ->keyBy('reactor_id'); // last write per reactor wins (ordered asc)

            $trends = [];
            foreach ($this->fleet() as $plant) {
                foreach ($plant->reactors as $reactor) {
                    $base = $baseline[$reactor->id]->value ?? null;
                    if ($base === null || $reactor->latestRecord === null) {
                        continue;
                    }
                    $trends[$reactor->id] = (int) round($reactor->latestRecord->value - $base);
                }
            }

            return $trends;
        });
    }

    /** Total / coupled / stopped reactor counts across the whole fleet (for the filter chips). */
    #[Computed]
    public function counts(): array
    {
        $total = 0;
        $coupled = 0;
        foreach ($this->fleet() as $plant) {
            foreach ($plant->reactors as $reactor) {
                $total++;
                if (($reactor->latestRecord?->percent_value ?? 0) >= 5) {
                    $coupled++;
                }
            }
        }

        return ['toutes' => $total, 'couplees' => $coupled, 'arret' => $total - $coupled];
    }

    /**
     * Plants (as display rows) with their reactor units, after search / filter / sort.
     *
     * @return array<int,array<string,mixed>>
     */
    #[Computed]
    public function groups(): array
    {
        $trends = $this->trends();
        $search = trim($this->search);

        $plants = $this->fleet();

        $plants = match ($this->sort) {
            'nom' => $plants->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'charge' => $plants->sortByDesc->percent_value,
            default => $plants->sortByDesc->latest_production_mw,
        };

        return $plants
            ->when($search !== '', fn ($c) => $c->filter(
                fn (Plant $p) => str_contains(mb_strtolower($p->name), mb_strtolower($search))
            ))
            ->map(function (Plant $plant) use ($trends) {
                $units = $plant->reactors
                    ->sortBy('reactor_index')
                    ->map(fn ($reactor) => $this->unitRow($reactor, $trends))
                    ->filter(fn ($u) => match ($this->filter) {
                        'couplees' => $u['on'],
                        'arret' => ! $u['on'],
                        default => true,
                    })
                    ->values();

                if ($units->isEmpty()) {
                    return null;
                }

                $mw = $plant->latest_production_mw;
                $pct = $plant->percent_value;

                return [
                    'name' => $plant->name,
                    'slug' => $plant->slug,
                    'online' => $plant->active_reactors_count,
                    'total' => $plant->reactors->count(),
                    'palier' => $this->dominantPalier($plant),
                    'pn' => $plant->total_production_mw,
                    'mw' => $mw,
                    'pct' => (int) round($pct),
                    'barVar' => LoadColor::var($mw, $pct),
                    'units' => $units->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /** Build the display row for a single reactor. */
    protected function unitRow($reactor, array $trends): array
    {
        $value = $reactor->latestRecord?->value ?? 0;
        $pct = $reactor->latestRecord?->percent_value ?? 0;
        $on = $pct >= 5;
        $delta = $trends[$reactor->id] ?? null;

        return [
            'name' => $reactor->name,
            'index' => $reactor->reactor_index,
            'palier' => $reactor->stage,
            'pn' => (float) $reactor->net_power_mw,
            'value' => $value,
            'pct' => $pct,
            'on' => $on,
            'colorVar' => LoadColor::var($value, $pct),
            'status' => ReactorStatus::fromValues($value, $pct),
            'statusVar' => 'var('.($value < 0 ? '--color-danger' : ($on ? '--color-accent' : '--color-idle')).')',
            'trend' => $this->trendLabel($delta, $on),
            'trendVar' => 'var('.$this->trendToken($delta, $on).')',
        ];
    }

    protected function trendLabel(?int $delta, bool $on): string
    {
        if (! $on || $delta === null) {
            return '—';
        }

        return match (true) {
            $delta > 0 => '▲ +'.$delta.' MW',
            $delta < 0 => '▼ '.$delta.' MW',
            default => '→ stable',
        };
    }

    protected function trendToken(?int $delta, bool $on): string
    {
        if (! $on || $delta === null) {
            return '--color-idle';
        }

        return match (true) {
            $delta > 0 => '--color-accent',
            $delta < 0 => '--color-danger',
            default => '--color-faint',
        };
    }

    /** Most common palier (stage) among a plant's reactors. */
    protected function dominantPalier(Plant $plant): string
    {
        return $plant->reactors
            ->pluck('stage')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first() ?? '—';
    }

    /** Stream the current (sorted, unfiltered) fleet as a CSV, one row per reactor. */
    public function exportCsv(): StreamedResponse
    {
        $filename = 'tranches-nucleaires-'.Carbon::now()->format('Y-m-d-Hi').'.csv';

        // Export the full fleet ordered by the active sort, regardless of on/off filter.
        $rows = collect($this->groupsForExport());

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}"); // BOM so Excel reads UTF-8
            fputcsv($out, ['Centrale', 'Tranche', 'Palier', 'PN (MW)', 'Production (MW)', 'Charge (%)', 'Statut'], ';', '"', '');

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['plant'],
                    $row['name'],
                    $row['palier'],
                    $row['pn'],
                    $row['value'],
                    $row['pct'],
                    $row['status'],
                ], ';', '"', '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Flat reactor list for CSV, honouring the sort but not the on/off filter. */
    protected function groupsForExport(): array
    {
        $trends = $this->trends();

        $plants = match ($this->sort) {
            'nom' => $this->fleet()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'charge' => $this->fleet()->sortByDesc->percent_value,
            default => $this->fleet()->sortByDesc->latest_production_mw,
        };

        $out = [];
        foreach ($plants as $plant) {
            foreach ($plant->reactors->sortBy('reactor_index') as $reactor) {
                $u = $this->unitRow($reactor, $trends);
                $out[] = [
                    'plant' => $plant->name,
                    'name' => $u['name'],
                    'palier' => $u['palier'],
                    'pn' => $u['pn'],
                    'value' => $u['value'],
                    'pct' => $u['pct'],
                    'status' => $u['status'],
                ];
            }
        }

        return $out;
    }

    public function render()
    {
        return view('livewire.reactor-table');
    }
}
