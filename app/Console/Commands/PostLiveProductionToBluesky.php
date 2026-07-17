<?php

namespace App\Console\Commands;

use App\Services\BlueskyService;
use App\Services\NationalStats;
use App\Services\ShareImageService;
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
    protected $description = 'Post the current national nuclear production to Bluesky, with the national share card.';

    protected const SITE_TEXT = 'electronucleaire.fr';

    protected const SITE_URL = 'https://electronucleaire.fr';

    /**
     * Execute the console command.
     */
    public function handle(BlueskyService $bluesky): int
    {
        $stats = NationalStats::get();

        $text = $this->buildText($stats);
        $facets = $this->linkFacets($text);
        $imagePath = $this->nationalImagePath();
        $altText = $this->buildAltText($stats);

        try {
            $uri = $bluesky->post($text, $imagePath, $altText, $facets);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Posted to Bluesky: '.$uri);

        return self::SUCCESS;
    }

    /**
     * @param  array<string,mixed>  $stats
     */
    protected function buildText(array $stats): string
    {
        $gw = $this->fr($stats['injected_gw'], 1);
        $coupled = $stats['coupled'];
        $total = $stats['total_reactors'];
        $load = $stats['load_factor_pct'];
        $date = now()->locale('fr')->isoFormat('D MMM YYYY').' à '.now()->format('H\hi');

        return "☢️ Production nucléaire française\n\n"
            ."{$gw} GW injectés · {$coupled}/{$total} réacteurs couplés · {$load} % de charge\n\n"
            ."{$date} — ".self::SITE_TEXT;
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
     * Path to the national share card, rendering it first if it's missing.
     */
    protected function nationalImagePath(): ?string
    {
        $path = storage_path('app/public/'.ShareImageService::DIR.'/national.png');

        if (! is_file($path)) {
            try {
                ShareImageService::national();
            } catch (\Throwable $e) {
                Log::warning('Bluesky post: national share-image render failed: '.$e->getMessage());
            }
        }

        if (! is_file($path)) {
            $this->warn('National share image not found; posting text only.');

            return null;
        }

        return $path;
    }

    /** French number formatting: space thousands, comma decimals. */
    protected function fr(float|int $value, int $decimals = 0): string
    {
        return Number::format($value, $decimals, $decimals, locale: 'fr');
    }
}
