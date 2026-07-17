<?php

namespace App\Services;

use App\Models\Plant;
use App\Support\BrowsershotFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Screenshots the live site's real pages (homepage map+list, /tableau table,
 * per-plant detail with its stacked reactor chart) to JPEGs under
 * storage/app/public/social/, for the Bluesky share bot.
 *
 * JPEG (not PNG) keeps map/photo-heavy captures well under Bluesky's ~1 MB
 * per-blob limit. Every method is best-effort: on any failure it logs and
 * returns null so a broken screenshot never aborts the post.
 */
class SocialShotService
{
    public const DIR = 'social';

    /** Width of every capture. */
    protected const WIDTH = 1200;

    /**
     * Window heights sized to fit the whole page for the current fleet
     * (18 plants / 57 reactors) — the app shell is `h-screen overflow-hidden`,
     * so a taller window enlarges the internally-scrolled sidebar/table and
     * reveals every row. Err tall: too short cuts content, too tall only adds a
     * little trailing whitespace. Bump these if the fleet grows.
     */
    protected const H_HOME = 1120;

    protected const H_TABLEAU = 3200;

    protected const H_PLANT = 1000;

    /** JPEG quality for the captures. */
    protected const QUALITY = 85;

    /** Retina by default; drop to 1 if a shot exceeds Bluesky's ~1 MB blob limit. */
    protected const MAX_BYTES = 950_000;

    /** Homepage: Leaflet map + plant list. Needs OSM tiles (network). */
    public function home(): ?string
    {
        return $this->capture(route('home'), 'home', self::H_HOME, 2500);
    }

    /** /tableau: full per-plant/per-reactor table (server-rendered). */
    public function tableau(): ?string
    {
        return $this->capture(route('table'), 'tableau', self::H_TABLEAU, 1200);
    }

    /** Per-plant detail page, including the stacked all-reactor chart. */
    public function plant(Plant $plant): ?string
    {
        return $this->capture(route('plant', $plant->slug), 'plant-'.$plant->slug, self::H_PLANT, 2500);
    }

    /**
     * Screenshot $url to storage/app/public/social/{$basename}.jpg and return
     * its absolute path, or null on failure. Renders at deviceScaleFactor 2 for
     * crispness, falling back to 1 if the file would exceed Bluesky's blob limit.
     */
    protected function capture(string $url, string $basename, int $height, int $delayMs): ?string
    {
        Storage::disk('public')->makeDirectory(self::DIR);
        $path = storage_path('app/public/'.self::DIR.'/'.$basename.'.jpg');

        foreach ([2, 1] as $scale) {
            try {
                BrowsershotFactory::url($url)
                    ->windowSize(self::WIDTH, $height)
                    ->deviceScaleFactor($scale)
                    ->setScreenshotType('jpeg', self::QUALITY)
                    ->emulateMediaFeatures([
                        ['name' => 'prefers-color-scheme', 'value' => $this->isDark() ? 'dark' : 'light'],
                    ])
                    ->waitUntilNetworkIdle()
                    ->setDelay($delayMs)
                    ->save($path);
            } catch (Throwable $e) {
                Log::warning("Social screenshot failed for {$url}: ".$e->getMessage());

                return null;
            }

            // Accept a scale-2 render only if it fits the blob limit; otherwise retry at scale 1.
            if ($scale === 1 || (is_file($path) && filesize($path) <= self::MAX_BYTES)) {
                break;
            }
        }

        return is_file($path) ? $path : null;
    }

    /** Night-time (22h-6h) renders match the site's dark theme, like the OG cards. */
    protected function isDark(): bool
    {
        $hour = now()->hour;

        return $hour >= 22 || $hour < 6;
    }
}
