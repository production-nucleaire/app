<?php

namespace App\Support;

class Sparkline
{
    public static function render(array $points, int $width = 150, int $height = 34, string $stroke = '#124a63', string $fill = 'rgba(18,74,99,0.08)', array $labels = []): string
    {
        $count = count($points);
        if ($count < 2) {
            return "<svg viewBox=\"0 0 {$width} {$height}\" xmlns=\"http://www.w3.org/2000/svg\" style=\"display:block;width:100%;height:{$height}px\"></svg>";
        }

        $min = min($points);
        $max = max($points);
        $range = ($max - $min) ?: 1;
        $pad = 4;
        $lb = $labels ? 16 : 3;

        $coords = [];
        foreach (array_values($points) as $i => $v) {
            $x = $pad + $i * ($width - 2 * $pad) / ($count - 1);
            $y = $height - $lb - ($v - $min) / $range * ($height - $lb - 10);
            $coords[] = [round($x, 1), round($y, 1)];
        }

        $line = implode(' ', array_map(fn ($p) => "{$p[0]},{$p[1]}", $coords));
        $area = $line.' '.($width - $pad).','.($height - $lb).' '.$pad.','.($height - $lb);
        $last = end($coords);

        $labelSvg = '';
        if ($labels) {
            $n = count($labels);
            foreach (array_values($labels) as $i => $t) {
                $x = $pad + $i * ($width - 2 * $pad) / max($n - 1, 1);
                $anchor = $i === 0 ? 'start' : ($i === $n - 1 ? 'end' : 'middle');
                $t = htmlspecialchars($t, ENT_QUOTES);
                $labelSvg .= "<text x=\"{$x}\" y=\"".($height - 3)."\" font-size=\"9.5\" font-family=\"Spline Sans Mono,monospace\" fill=\"#a0a096\" text-anchor=\"{$anchor}\">{$t}</text>";
            }
        }

        return <<<SVG
            <svg viewBox="0 0 {$width} {$height}" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" style="display:block;width:100%;height:{$height}px">
                <polygon points="{$area}" fill="{$fill}" />
                <polyline points="{$line}" fill="none" stroke="{$stroke}" stroke-width="2" />
                <circle cx="{$last[0]}" cy="{$last[1]}" r="3" fill="{$stroke}" />
                {$labelSvg}
            </svg>
        SVG;
    }
}
