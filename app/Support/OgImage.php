<?php

namespace App\Support;

use App\Jobs\GeneratePlantShareImage;
use App\Models\Plant;
use App\Services\ShareImageService;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves the Open Graph metadata (image URL + title + description) for the
 * current route, so the layout <head> can advertise the right share card.
 *
 * National + default images are refreshed by the hourly import; plant images
 * are generated on demand — a stale/missing one dispatches a queued render and
 * meanwhile falls back to the freshest available card.
 */
class OgImage
{
    /** Regenerate a plant card if the cached PNG is older than this. */
    protected const STALE_AFTER = 7200; // 2h

    protected const SITE = 'électronucléaire.fr';

    protected const TAGLINE = 'La production du parc nucléaire français, heure par heure — données RTE.';

    public static function forCurrentRoute(): array
    {
        $route = request()->route();
        $name = $route?->getName();

        if (in_array($name, ['plant', 'reactor'], true)) {
            return self::forPlant((string) $route->parameter('slug'));
        }

        if ($name === 'home') {
            return self::meta('national', 'Le parc nucléaire français, en direct', self::TAGLINE);
        }

        return self::meta('default', self::SITE, self::TAGLINE);
    }

    protected static function forPlant(string $slug): array
    {
        $plant = Plant::where('slug', $slug)->orWhere('name', $slug)->first();

        if (! $plant) {
            return self::meta('default', self::SITE, self::TAGLINE);
        }

        $basename = 'plant-'.$plant->slug;
        $path = ShareImageService::DIR.'/'.$basename.'.png';
        $disk = Storage::disk('public');
        $exists = $disk->exists($path);
        $stale = ! $exists || ($disk->lastModified($path) < now()->timestamp - self::STALE_AFTER);

        // Refresh in the background. Skipped under tests so page renders don't
        // spawn a real Browsershot job on the sync queue.
        if ($stale && ! app()->runningUnitTests()) {
            GeneratePlantShareImage::dispatch($plant->id);
        }

        // Serve the plant card once it exists (even while a refresh is queued);
        // fall back to the always-fresh national card until the first render lands.
        $card = $exists ? $basename : 'national';

        return self::meta(
            $card,
            $plant->name.' — production nucléaire en direct',
            'Suivi heure par heure de la centrale de '.$plant->name.' — données RTE.'
        );
    }

    protected static function meta(string $basename, string $title, string $description): array
    {
        return [
            'image' => Storage::disk('public')->url(ShareImageService::DIR.'/'.$basename.'.png'),
            'title' => $title,
            'description' => $description,
            'url' => request()->url(),
        ];
    }
}
