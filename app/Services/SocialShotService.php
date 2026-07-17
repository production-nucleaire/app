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

    /** Homepage: Leaflet map + plant list. Needs OSM tiles (network). */
    public function home(): ?string
    {
        return $this->capture(route('home'), 'home', 1200, 800, 2500);
    }

    /** /tableau: full per-plant/per-reactor table (server-rendered). */
    public function tableau(): ?string
    {
        return $this->capture(route('table'), 'tableau', 1200, 1500, 1200);
    }

    /** Per-plant detail page, including the stacked all-reactor chart. */
    public function plant(Plant $plant): ?string
    {
        return $this->capture(route('plant', $plant->slug), 'plant-'.$plant->slug, 1200, 800, 2500);
    }

    /**
     * Screenshot $url to storage/app/public/social/{$basename}.jpg and return
     * its absolute path, or null on failure.
     */
    protected function capture(string $url, string $basename, int $width, int $height, int $delayMs): ?string
    {
        Storage::disk('public')->makeDirectory(self::DIR);
        $path = storage_path('app/public/'.self::DIR.'/'.$basename.'.jpg');

        try {
            BrowsershotFactory::url($url)
                ->windowSize($width, $height)
                ->setScreenshotType('jpeg', 80)
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

        return is_file($path) ? $path : null;
    }

    /** Night-time (22h-6h) renders match the site's dark theme, like the OG cards. */
    protected function isDark(): bool
    {
        $hour = now()->hour;

        return $hour >= 22 || $hour < 6;
    }
}
