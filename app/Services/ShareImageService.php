<?php

namespace App\Services;

use App\Models\Plant;
use App\Models\Reactor;
use App\Support\OgSvg;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Spatie\Browsershot\Browsershot;

/**
 * Renders the three Open Graph share images (national / plant / default) to
 * PNG via Browsershot and stores them on the public disk under og/.
 *
 * - Blade views live in resources/views/og/*, inline SVG comes from OgSvg.
 * - A dark variant is used for night-time renders (22h-6h).
 * - Every image is written at 1x (1200x630) and 2x (2400x1260).
 *
 * The live "now" path is what the hourly import and the artisan command call.
 */
class ShareImageService
{
    public const DIR = 'og';

    public const W = 1200;

    public const H = 630;

    /** Render og/national.png (+ @2x). */
    public static function national(?Carbon $at = null): void
    {
        $at ??= now();
        self::render(view('og.national', self::nationalData($at))->render(), 'national');
    }

    /** Render og/default.png (+ @2x). */
    public static function default(?Carbon $at = null): void
    {
        $at ??= now();
        self::render(view('og.default', self::defaultData($at))->render(), 'default');
    }

    /** Render og/plant-{slug}.png (+ @2x). */
    public static function plant(Plant $plant, ?Carbon $at = null): void
    {
        $at ??= now();
        self::render(view('og.plant', self::plantData($plant, $at))->render(), 'plant-'.$plant->slug);
    }

    // ---- data assembly -----------------------------------------------------

    public static function nationalData(Carbon $at): array
    {
        $stats = NationalStats::get();
        $plants = Plant::with('reactors.latestRecord')->get();

        $dots = $plants
            ->filter(fn (Plant $p) => $p->latitude !== null && $p->longitude !== null)
            ->map(fn (Plant $p) => [
                'lon' => (float) $p->longitude,
                'lat' => (float) $p->latitude,
                'status' => self::plantStatus($p),
            ])->values()->all();

        $deltaGw = self::hourlyDeltaGw($stats['spark24h']);

        return [
            'dark' => self::isDark($at),
            'dateLabel' => self::dateLabel($at),
            'gw' => self::fr($stats['injected_gw'], 1),
            'deltaText' => $deltaGw === null ? null : ($deltaGw >= 0 ? '▲ +' : '▼ ').self::fr(abs($deltaGw), 1).' GW',
            'deltaColor' => ($deltaGw ?? 0) >= 0 ? 'green' : 'neg',
            'coupled' => $stats['coupled'],
            'totalReactors' => $stats['total_reactors'],
            'loadPct' => $stats['load_factor_pct'],
            'plantsCount' => $plants->filter(fn (Plant $p) => $p->active_reactors_count > 0)->count(),
            'highlights' => self::highlights($plants),
            'mapSvg' => OgSvg::franceMap($dots, self::isDark($at)),
        ];
    }

    public static function plantData(Plant $plant, Carbon $at): array
    {
        $plant->load(['reactors' => fn ($q) => $q->orderBy('reactor_index'), 'reactors.latestRecord']);
        $reactors = $plant->reactors;

        $chips = $reactors->map(function (Reactor $r) {
            $status = self::reactorStatus($r);
            $value = (int) ($r->latestRecord?->value ?? 0);
            $label = $status === 'off'
                ? 'T'.$r->reactor_index.' · maintenance'
                : 'T'.$r->reactor_index.' · '.self::fr($value).' MW';

            return ['status' => $status, 'label' => $label];
        })->all();

        $values = self::plant24h($plant);
        $max = (float) $reactors->sum('net_power_mw');

        return [
            'dark' => self::isDark($at),
            'dateLabel' => self::dateLabel($at),
            'descriptor' => self::plantDescriptor($plant),
            'name' => $plant->name,
            'mw' => self::fr(round($plant->latest_production_mw)),
            'mwColor' => $plant->latest_production_mw < 0 ? 'neg' : 'green',
            'loadPct' => (int) round($plant->percent_value),
            'coupledCount' => $plant->active_reactors_count,
            'totalCount' => $reactors->count(),
            'chips' => $chips,
            'chartSvg' => OgSvg::areaChart($values, 1120, 250, $max, self::isDark($at)),
        ];
    }

    public static function defaultData(Carbon $at): array
    {
        $stats = NationalStats::get();

        return [
            'plantsCount' => Plant::count(),
            'reactorsCount' => Reactor::count(),
            'sparkSvg' => OgSvg::sparkline($stats['spark24h'], 1200, 190),
        ];
    }

    // ---- helpers -----------------------------------------------------------

    /** Per-plant map/legend status. */
    protected static function plantStatus(Plant $plant): string
    {
        if ($plant->latest_production_mw < 0) {
            return 'neg';
        }
        if ($plant->active_reactors_count === 0) {
            return 'off';
        }

        return $plant->percent_value >= 80 ? 'coupled' : 'partial';
    }

    /** Per-reactor chip status: full / partial / off. */
    protected static function reactorStatus(Reactor $reactor): string
    {
        $record = $reactor->latestRecord;
        if ($record === null || $reactor->net_power_mw <= 0) {
            return 'off';
        }

        $pct = ($record->value / $reactor->net_power_mw) * 100;
        if ($pct < 5) {
            return 'off';
        }

        return $pct >= 80 ? 'full' : 'partial';
    }

    /** Up to two contextual highlight bullets for the national card. */
    protected static function highlights(Collection $plants): array
    {
        $out = [];

        $offline = $plants->filter(fn (Plant $p) => $p->active_reactors_count === 0 && $p->reactors->isNotEmpty());
        if ($offline->count() === 1) {
            $p = $offline->first();
            $out[] = ['color' => 'neg', 'text' => $p->name.' entièrement hors réseau (0/'.$p->reactors->count().' tranches)'];
        } elseif ($offline->count() > 1) {
            $out[] = ['color' => 'neg', 'text' => $offline->count().' centrales entièrement hors réseau'];
        }

        $high = $plants->filter(fn (Plant $p) => $p->active_reactors_count > 0 && $p->percent_value >= 90)
            ->sortByDesc(fn (Plant $p) => $p->percent_value)
            ->pluck('name')->values();
        if ($high->isNotEmpty()) {
            $names = $high->take(3)->all();
            $joined = count($names) > 1
                ? implode(', ', array_slice($names, 0, -1)).' et '.end($names)
                : $names[0];
            $out[] = ['color' => 'green', 'text' => $joined.' à plus de 90 % de charge'];
        }

        // Fall back to the top producer so the card is never empty.
        if ($out === []) {
            $top = $plants->sortByDesc(fn (Plant $p) => $p->latest_production_mw)->first();
            if ($top) {
                $out[] = ['color' => 'coupled', 'text' => $top->name.' en tête avec '.self::fr(round($top->latest_production_mw)).' MW'];
            }
        }

        return array_slice($out, 0, 2);
    }

    protected static function plantDescriptor(Plant $plant): string
    {
        $reactors = $plant->reactors;
        $count = $reactors->count();
        $region = $plant->cooling_place ? mb_strtoupper($plant->cooling_place) : null;

        $powers = $reactors->pluck('net_power_mw')->unique();
        $power = $powers->count() === 1
            ? $count.' × '.self::fr((int) $powers->first()).' MW'
            : $count.' tranches';

        $palier = $reactors->pluck('stage')->filter()->countBy()->sortDesc()->keys()->first();

        return collect(['CENTRALE NUCLÉAIRE', $region, $power, $palier])->filter()->implode(' · ');
    }

    /** Hourly production for the last 24h, summed across the plant's reactors. */
    protected static function plant24h(Plant $plant): array
    {
        return $plant->records()
            ->whereBetween('date', [now()->subHours(24), now()])
            ->orderBy('date')
            ->get(['date', 'value'])
            ->groupBy(fn ($r) => $r->date->format('Y-m-d H'))
            ->map(fn (Collection $g) => (int) $g->sum('value'))
            ->values()
            ->all();
    }

    protected static function hourlyDeltaGw(array $spark): ?float
    {
        $n = count($spark);
        if ($n < 2) {
            return null;
        }

        return round(($spark[$n - 1] - $spark[$n - 2]) / 1000, 1);
    }

    protected static function isDark(Carbon $at): bool
    {
        return $at->hour >= 22 || $at->hour < 6;
    }

    protected static function dateLabel(Carbon $at): string
    {
        return $at->locale('fr')->isoFormat('D MMM YYYY').' · '.$at->format('H\hi');
    }

    /** French number formatting: space thousands, comma decimals. */
    protected static function fr(float|int $value, int $decimals = 0): string
    {
        return Number::format($value, $decimals, $decimals, locale: 'fr');
    }

    // ---- rendering ---------------------------------------------------------

    protected static function render(string $html, string $basename): void
    {
        Storage::disk('public')->makeDirectory(self::DIR);

        self::shot($html)
            ->save(storage_path('app/public/'.self::DIR.'/'.$basename.'.png'));

        self::shot($html)
            ->deviceScaleFactor(2)
            ->save(storage_path('app/public/'.self::DIR.'/'.$basename.'@2x.png'));
    }

    protected static function shot(string $html): Browsershot
    {
        $shot = Browsershot::html($html)
            ->windowSize(self::W, self::H)
            ->setScreenshotType('png')
            ->waitUntilNetworkIdle();

        $cfg = config('services.browsershot');
        if (! empty($cfg['node_binary'])) {
            $shot->setNodeBinary($cfg['node_binary']);
        }
        if (! empty($cfg['npm_binary'])) {
            $shot->setNpmBinary($cfg['npm_binary']);
        }
        if (! empty($cfg['chrome_path'])) {
            $shot->setChromePath($cfg['chrome_path']);
        }
        if (! empty($cfg['node_module_path'])) {
            $shot->setNodeModulePath($cfg['node_module_path']);
        }

        return $shot;
    }
}
