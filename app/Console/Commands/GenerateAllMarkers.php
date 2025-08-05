<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SvgService;

class GenerateAllMarkers extends Command
{
    protected $signature = 'app:generate-all-markers {--max=6}';

    protected $description = 'Pre-generate SVG markers for all reactor combinations';

    public function handle(SvgService $svg)
    {
        $max = (int) $this->option('max');

        foreach (range(2, 6) as $total) {
            foreach (range(1, $total - 1) as $active) {
                $path = $svg->storeMarker($active, $total);
                $this->line("Generated: marker-{$active}-{$total}.svg → $path");
                $path = $svg->storeMarker($active, $total, 'red');
                $this->line("Generated: marker-{$active}-{$total}-selected.svg → $path");
            }
        }

        $svg->storeMarker(0, 1);
        $svg->storeMarker(0, 1, 'red');
        $svg->storeMarker(1, 1);
        $svg->storeMarker(1, 1, 'red');

        $this->info('All marker combinations generated.');
    }
}