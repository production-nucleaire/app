<?php

namespace App\Console\Commands;

use App\Models\Plant;
use App\Services\BlueskyService;
use App\Services\NationalStats;
use App\Services\ProductionMovers;
use App\Services\ShareImageService;
use App\Services\SocialShotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use RuntimeException;

class PostLiveProductionToBluesky extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:post-live-production-to-bluesky';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post the current national nuclear production to Bluesky, with live screenshots of the site.';

    protected const SITE_TEXT = 'electronucleaire.fr';

    protected const SITE_URL = 'https://electronucleaire.fr';

    /**
     * Execute the console command.
     */
    public function handle(BlueskyService $bluesky, SocialShotService $shots): int
    {
        $stats = NationalStats::get();
        $movers = ProductionMovers::top(2);

        $text = $this->buildText($stats, $movers);
        $facets = $this->linkFacets($text);

        // National OG card (2x) as the headline image, then live map + table shots.
        $images = [];
        if ($card = $this->nationalCardPath()) {
            $images[] = ['path' => $card, 'alt' => $this->buildAltText($stats)];
        }
        if ($home = $shots->home()) {
            $images[] = ['path' => $home, 'alt' => 'Carte interactive de la production nucléaire française et liste des centrales.'];
        }
        if ($table = $shots->tableau()) {
            $images[] = ['path' => $table, 'alt' => 'Tableau détaillé de la production par centrale et par réacteur (électronucléaire.fr).'];
        }

        try {
            $uri = $bluesky->post($text, $images, $facets);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Posted to Bluesky with '.count($images).' image(s): '.$uri);

        return self::SUCCESS;
    }

    /**
     * @param  array<string,mixed>  $stats
     * @param  array<int,array{plant: Plant, delta: int, current: int}>  $movers
     */
    protected function buildText(array $stats, array $movers = []): string
    {
        $gw = $this->fr($stats['injected_gw'], 1);
        $coupled = $stats['coupled'];
        $total = $stats['total_reactors'];
        $load = $stats['load_factor_pct'];
        $date = now()->locale('fr')->isoFormat('D MMM YYYY').' à '.now()->format('H\hi');

        $headline = "{$gw} GW injectés";
        if ($trend = $this->hourlyTrend($stats['spark24h'] ?? [])) {
            $headline .= " ({$trend})";
        }

        $text = "⚛️ Production nucléaire française ⚛️\n\n"
            ."{$headline} · {$coupled}/{$total} réacteurs couplés · {$load} % de charge\n\n";

        if ($movers !== []) {
            $parts = array_map(fn (array $m) => $m['plant']->name.' '.$this->signedMw($m['delta']), $movers);
            $text .= 'Plus fortes variations sur la dernière heure : '.implode(' · ', $parts)."\n\n";
        }

        return $text."{$date} — ".self::SITE_TEXT;
    }

    /**
     * National hourly trend from the 24h spark buckets, e.g. "▲ +0,3 GW/h".
     * Null when there aren't two buckets or the change rounds to zero.
     *
     * @param  array<int,int>  $spark
     */
    protected function hourlyTrend(array $spark): ?string
    {
        $n = count($spark);
        if ($n < 2) {
            return null;
        }

        $deltaGw = round(($spark[$n - 1] - $spark[$n - 2]) / 1000, 1);
        if ($deltaGw === 0.0) {
            return null;
        }

        return ($deltaGw > 0 ? '▲ +' : '▼ −').$this->fr(abs($deltaGw), 1).' GW/h';
    }

    /** Signed MW change, e.g. "+320 MW" / "−150 MW". */
    protected function signedMw(int $delta): string
    {
        return ($delta >= 0 ? '+' : '−').$this->fr(abs($delta)).' MW';
    }

    /**
     * @param  array<string,mixed>  $stats
     */
    protected function buildAltText(array $stats): string
    {
        $gw = $this->fr($stats['injected_gw'], 1);

        return "Carte de la production nucléaire française : {$gw} GW injectés sur le réseau, "
            ."{$stats['coupled']} réacteurs couplés sur {$stats['total_reactors']}, "
            ."{$stats['load_factor_pct']} % de facteur de charge.";
    }

    /**
     * Build a single link facet pointing the site name at the site URL.
     *
     * AT Protocol facet offsets are UTF-8 byte positions, so we measure with
     * strpos/strlen (the text contains multi-byte accented characters).
     *
     * @return array<int,array<string,mixed>>
     */
    protected function linkFacets(string $text): array
    {
        $start = strpos($text, self::SITE_TEXT);
        if ($start === false) {
            return [];
        }

        return [[
            'index' => [
                'byteStart' => $start,
                'byteEnd' => $start + strlen(self::SITE_TEXT),
            ],
            'features' => [[
                '$type' => 'app.bsky.richtext.facet#link',
                'uri' => self::SITE_URL,
            ]],
        ]];
    }

    /**
     * Path to the national share card, preferring the @2x variant. Re-renders it
     * fresh so the posted card matches "now" (skipped under tests). Falls back to
     * the 1x file, then null.
     */
    protected function nationalCardPath(): ?string
    {
        if (! app()->runningUnitTests()) {
            try {
                ShareImageService::national();
            } catch (\Throwable $e) {
                Log::warning('Bluesky post: national share-image render failed: '.$e->getMessage());
            }
        }

        foreach (['national@2x.png', 'national.png'] as $name) {
            $path = storage_path('app/public/'.ShareImageService::DIR.'/'.$name);
            if (is_file($path)) {
                return $path;
            }
        }

        $this->warn('National share image not found.');

        return null;
    }

    /** French number formatting: space thousands, comma decimals. */
    protected function fr(float|int $value, int $decimals = 0): string
    {
        return Number::format($value, $decimals, $decimals, locale: 'fr');
    }
}
