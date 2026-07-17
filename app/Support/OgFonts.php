<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Emits the self-hosted @font-face block for the Open Graph images. The two
 * variable woff2 files (latin subset, which already covers French accents)
 * are base64-inlined so Browsershot never has to hit the network — the
 * scheduled prod render runs offline. The built <style> is cached forever.
 */
class OgFonts
{
    public static function styleTag(): string
    {
        return Cache::rememberForever('og:fonts:style', function () {
            $sans = self::dataUri('resources/fonts/og/instrument-sans-latin.woff2');
            $mono = self::dataUri('resources/fonts/og/spline-sans-mono-latin.woff2');

            return <<<CSS
            <style>
            @font-face{font-family:'Instrument Sans';font-style:normal;font-weight:400 700;font-display:block;src:url({$sans}) format('woff2');}
            @font-face{font-family:'Spline Sans Mono';font-style:normal;font-weight:400 600;font-display:block;src:url({$mono}) format('woff2');}
            </style>
            CSS;
        });
    }

    protected static function dataUri(string $relative): string
    {
        $data = base64_encode((string) file_get_contents(base_path($relative)));

        return 'data:font/woff2;base64,'.$data;
    }
}
