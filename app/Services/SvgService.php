<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class SvgService
{
    public function generateMarker(int $active, int $total, string $color = 'blue'): string
    {
        $color = 'red' === $color ? '#fb2c36' : '#0084d1';
        $green = '#00c950';
        $fill = $active > 0 ? $green : '#fff';

        $cx = 256;
        $cy = 212;
        $radius = 137;
        $angleStep = 360 / $total;
        $rotationOffset = -90;
        $paths = '';

        for ($i = 0; $i < $total; $i++) {
            $startAngle = $angleStep * $i + $rotationOffset;
            $endAngle = $angleStep * ($i + 1) + $rotationOffset;
            $largeArc = $angleStep > 180 ? 1 : 0;

            $x1 = $cx + $radius * cos(deg2rad($startAngle));
            $y1 = $cy + $radius * sin(deg2rad($startAngle));
            $x2 = $cx + $radius * cos(deg2rad($endAngle));
            $y2 = $cy + $radius * sin(deg2rad($endAngle));

            $className = $i < $active
                ? "style=\"fill:$green\""
                : 'style="fill:#fff; stroke-width:8;stroke:#fff;"';

            $paths .= "<path d=\"M{$cx},{$cy} L{$x1},{$y1} A{$radius},{$radius} 0 {$largeArc} 1 {$x2},{$y2} Z\" {$className} />";
        }

        return <<<SVG
            <svg width="40" height="40" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                <g>
                    <!-- Pin shape -->
                    <path d="m407.57 62.538a214.108 214.108 0 0 0 -365.678 151.57 212.833 212.833 0 0 0 62.538 151.571l142.609 142.609a12.672 12.672 0 0 0 17.922 0l142.609-142.609a214.946 214.946 0 0 0 0-303.141zm-151.57 287.368c-75.61 0-137.123-61.514-137.123-137.124s61.513-137.124 137.123-137.124 137.124 61.513 137.124 137.124-61.514 137.124-137.124 137.124z" fill="$color" />
                    <!-- Pie chart -->
                    <circle cx="256" cy="212" r="137" fill="$fill" />
                    $paths
                </g>
            </svg>
        SVG;
    }

    public function storeMarker(int $active, int $total, bool $selected = false): string
    {
        if ($active <= 0) {
            $name = "marker-empty";
        } elseif ($active === $total) {
            $name = "marker-full";
        } else {
            $name = "marker-{$active}-{$total}";
        }

        $directory = 'markers';
        $filename = "$directory/$name" . ($selected ? '-selected' : '') . ".svg";

        if (!Storage::exists($filename)) {
            // Ensure the directory exists
            Storage::makeDirectory($directory);

            // Write the file
            Storage::disk('public')->put($filename, $this->generateMarker($active, $total, $selected ? 'red' : 'blue'));
        }

        return Storage::url($filename); // returns /storage/markers/marker_2_6.svg
    }
}