<?php

namespace App\Support;

/**
 * Builds the inline SVG fragments embedded in the Open Graph share images
 * (see resources/views/og/*). Everything here is presentation-only: colours
 * are chosen from a light/dark palette and geometry is projected/scaled to a
 * fixed viewBox so the fragments drop straight into the Blade layouts.
 *
 * Kept separate from SvgService (which renders the Leaflet map-pin markers).
 */
class OgSvg
{
    /** Monotonic id source so gradients/clips never collide within one document. */
    protected static int $seq = 0;

    /**
     * Tile-less map of metropolitan France with one status-coloured dot per
     * plant. $dots is a list of ['lon' => float, 'lat' => float, 'status' =>
     * 'coupled'|'partial'|'off'|'neg'] — projected with the exact same
     * constants the outline path was baked with, so dots land on the coast.
     */
    public static function franceMap(array $dots, bool $dark = false): string
    {
        $fr = self::france();
        $p = $fr['proj'];

        $land = $dark ? '#14313d' : '#e6ede8';
        $stroke = $dark ? '#295260' : '#c9d5ce';
        $halo = $dark ? '#0d2731' : '#f4f7f5';
        $palette = self::statusColors($dark);

        $project = function (float $lon, float $lat) use ($p): array {
            $x = $p['offX'] + (($lon * $p['COS_LAT']) - $p['rxmin']) * $p['scale'];
            $y = $p['offY'] + ((-$lat) - $p['rymin']) * $p['scale'];

            return [round($x, 1), round($y, 1)];
        };

        $circles = '';
        foreach ($dots as $dot) {
            [$x, $y] = $project((float) $dot['lon'], (float) $dot['lat']);
            $color = $palette[$dot['status']] ?? $palette['off'];
            $r = $dot['r'] ?? 7;
            $circles .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.$r.'" fill="'.$color.'" stroke="'.$halo.'" stroke-width="2.5"/>';
        }

        return <<<SVG
        <svg viewBox="{$fr['viewBox']}" width="100%" height="100%" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg">
            <path d="{$fr['d']}" fill="{$land}" stroke="{$stroke}" stroke-width="1.4" stroke-linejoin="round"/>
            {$circles}
        </svg>
        SVG;
    }

    /**
     * Smooth line + filled area chart (24 h production). $values are numeric,
     * oldest→newest; normalised against $max (defaults to the series peak).
     * Mirrors the quadratic-Bézier smoothing used by PlantProductionChart.
     */
    public static function areaChart(array $values, int $width, int $height, ?float $max = null, bool $dark = false): string
    {
        $line = $dark ? '#2ecc80' : '#0d8a4f';
        $grid = $dark ? '#22454f' : '#e4e7e7';

        $values = array_values(array_map('floatval', $values));
        $count = count($values);
        $max = $max ?: (max($values) ?: 1);
        $padX = 4;
        $padTop = 12;
        $baseline = $height - 1;

        if ($count < 2 || $max <= 0) {
            return self::svg($width, $height, '<line x1="0" y1="'.$baseline.'" x2="'.$width.'" y2="'.$baseline.'" stroke="'.$grid.'" stroke-width="1"/>');
        }

        $points = [];
        foreach ($values as $i => $v) {
            $x = $padX + ($i / ($count - 1)) * ($width - 2 * $padX);
            $y = $padTop + (1 - min($v / $max, 1)) * ($baseline - $padTop);
            $points[] = [round($x, 1), round($y, 1)];
        }

        [$linePath, $areaPath] = self::bezier($points, $baseline);
        $id = 'ogc'.(++self::$seq);

        $paths = '<line x1="0" y1="'.$baseline.'" x2="'.$width.'" y2="'.$baseline.'" stroke="'.$grid.'" stroke-width="1"/>'
            .'<path d="'.$areaPath.'" fill="url(#'.$id.')" stroke="none"/>'
            .'<path d="'.$linePath.'" fill="none" stroke="'.$line.'" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/>';

        $defs = '<defs><linearGradient id="'.$id.'" x1="0" y1="0" x2="0" y2="1">'
            .'<stop offset="0" stop-color="'.$line.'" stop-opacity="0.28"/>'
            .'<stop offset="1" stop-color="'.$line.'" stop-opacity="0.02"/>'
            .'</linearGradient></defs>';

        return self::svg($width, $height, $defs.$paths);
    }

    /**
     * Faint full-width sparkline sitting along the bottom of the dark default
     * card (option 4c). Teal, fading upward, no axis.
     */
    public static function sparkline(array $values, int $width, int $height): string
    {
        $values = array_values(array_map('floatval', $values));
        $count = count($values);
        $max = max($values ?: [1]) ?: 1;

        if ($count < 2) {
            return self::svg($width, $height, '');
        }

        $padTop = 18;
        $baseline = $height;
        $points = [];
        foreach ($values as $i => $v) {
            $x = ($i / ($count - 1)) * $width;
            $y = $padTop + (1 - min($v / $max, 1)) * ($baseline - $padTop);
            $points[] = [round($x, 1), round($y, 1)];
        }

        [$linePath, $areaPath] = self::bezier($points, $baseline);
        $id = 'ogs'.(++self::$seq);

        $defs = '<defs><linearGradient id="'.$id.'" x1="0" y1="0" x2="0" y2="1">'
            .'<stop offset="0" stop-color="#7fd4a8" stop-opacity="0.30"/>'
            .'<stop offset="1" stop-color="#7fd4a8" stop-opacity="0"/>'
            .'</linearGradient></defs>';

        $paths = '<path d="'.$areaPath.'" fill="url(#'.$id.')" stroke="none"/>'
            .'<path d="'.$linePath.'" fill="none" stroke="#7fd4a8" stroke-width="2.5" stroke-opacity="0.55" stroke-linejoin="round" stroke-linecap="round"/>';

        return self::svg($width, $height, $defs.$paths);
    }

    /** Status → hex, light or dark variant. Matches the design legend. */
    public static function statusColors(bool $dark = false): array
    {
        return $dark
            ? ['coupled' => '#2ecc80', 'partial' => '#4bab84', 'off' => '#47616d', 'neg' => '#e0623a']
            : ['coupled' => '#0d8a4f', 'partial' => '#5fb98a', 'off' => '#c3ccc7', 'neg' => '#b5471d'];
    }

    /** Cached raw markup of the brand icon (resources/images/logo.svg). */
    protected static ?string $logo = null;

    /**
     * The static brand icon (logo.svg) inlined at a fixed pixel size, for the
     * OG card headers. The file's own width/height are stripped so our size
     * wins; the artwork keeps its baked colours and rounded corners.
     */
    public static function logo(int $size): string
    {
        if (self::$logo === null) {
            $svg = file_get_contents(resource_path('images/logo.svg'));
            $svg = preg_replace('/<\?xml.*?\?>/s', '', $svg);
            $svg = preg_replace('/<!--.*?-->/s', '', $svg);
            $svg = preg_replace('/\s(?:width|height)="[^"]*"/', '', $svg, 2);
            self::$logo = trim($svg);
        }

        return preg_replace(
            '/<svg\b/',
            '<svg width="'.$size.'" height="'.$size.'" style="display:block"',
            self::$logo,
            1
        );
    }

    /**
     * Quadratic-Bézier smoothing shared by the charts. Returns [linePath,
     * areaPath]; the area drops to $baseline and closes.
     */
    protected static function bezier(array $points, float $baseline): array
    {
        $line = 'M '.implode(',', $points[0]);
        for ($i = 1, $n = count($points); $i < $n; $i++) {
            $p0 = $points[$i - 1];
            $p1 = $points[$i];
            $mid = [($p0[0] + $p1[0]) / 2, ($p0[1] + $p1[1]) / 2];
            $line .= ' Q '.implode(',', $p0).' '.implode(',', $mid);
        }

        $lastX = $points[count($points) - 1][0];
        $firstX = $points[0][0];
        $area = $line." L {$lastX},{$baseline} L {$firstX},{$baseline} Z";

        return [$line, $area];
    }

    protected static function svg(int $width, int $height, string $body): string
    {
        return '<svg viewBox="0 0 '.$width.' '.$height.'" width="100%" height="100%" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">'.$body.'</svg>';
    }

    /** France outline path + projection constants, cached for the request. */
    protected static array $france = [];

    protected static function france(): array
    {
        if (self::$france === []) {
            self::$france = json_decode(file_get_contents(resource_path('data/france.json')), true);
        }

        return self::$france;
    }
}
