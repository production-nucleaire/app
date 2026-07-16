<?php

namespace App\Console\Commands;

use App\Models\Plant;
use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;

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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalProduction = Plant::all()->sum('latestProductionMw');

        dd($totalProduction);

        $pathToImage = storage_path('app/public/snapshots/example.png');

        Browsershot::url('https://electronucleaire.fr')
            ->windowSize(1200, 780)
            ->setDelay(1000)
            ->emulateMediaFeatures([
                ['name' => 'prefers-color-scheme', 'value' => 'dark'],
            ])
            // ->waitForSelector('.leaflet-container')
            // ->setScreenshotType('jpeg', 95)
            // ->deviceScaleFactor(2)
            ->save($pathToImage);
    }
}
