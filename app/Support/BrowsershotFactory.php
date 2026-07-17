<?php

namespace App\Support;

use Spatie\Browsershot\Browsershot;

/**
 * Central place that applies the app's headless-Chrome configuration
 * (config('services.browsershot')) to a Browsershot instance. The scheduled
 * prod import runs with a minimal PATH, so node/Chrome binaries usually have to
 * be set explicitly — see the BROWSERSHOT_* env vars.
 *
 * Used both by ShareImageService (HTML → OG cards) and SocialShotService
 * (URL → live-page screenshots).
 */
class BrowsershotFactory
{
    /** Build a Browsershot from HTML, with config applied. */
    public static function html(string $html): Browsershot
    {
        return self::configure(Browsershot::html($html));
    }

    /** Build a Browsershot from a URL, with config applied. */
    public static function url(string $url): Browsershot
    {
        return self::configure(Browsershot::url($url));
    }

    /** Apply the configured node/Chrome binaries and chromium arguments. */
    public static function configure(Browsershot $shot): Browsershot
    {
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
        if (! empty($cfg['chromium_arguments'])) {
            $shot->addChromiumArguments(explode(',', $cfg['chromium_arguments']));
        }

        return $shot;
    }
}
