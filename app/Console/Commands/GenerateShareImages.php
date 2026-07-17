<?php

namespace App\Console\Commands;

use App\Models\Plant;
use App\Services\ShareImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateShareImages extends Command
{
    protected $signature = 'app:generate-share-images
        {--national : Render the national card}
        {--default : Render the default brand card}
        {--plant= : Render a single plant by slug}
        {--all : Render national, default and every plant}
        {--date= : Timestamp context (Y-m-d or Y-m-d H:i) for the label/theme; defaults to now}';

    protected $description = 'Render the Open Graph share images (national / plant / default) to storage/app/public/og';

    public function handle(): int
    {
        $at = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        $all = (bool) $this->option('all');
        $national = $all || $this->option('national');
        $default = $all || $this->option('default');
        $plantSlug = $this->option('plant');

        // Default action: national + default.
        if (! $national && ! $default && ! $plantSlug && ! $all) {
            $national = $default = true;
        }

        if ($national) {
            $this->render('national', fn () => ShareImageService::national($at));
        }

        if ($default) {
            $this->render('default', fn () => ShareImageService::default($at));
        }

        if ($plantSlug) {
            $plant = Plant::where('slug', $plantSlug)->first();
            if (! $plant) {
                $this->error("Plant not found: {$plantSlug}");

                return self::FAILURE;
            }
            $this->render("plant-{$plant->slug}", fn () => ShareImageService::plant($plant, $at));
        }

        if ($all) {
            Plant::whereNotNull('slug')->orderBy('name')->get()->each(function (Plant $plant) use ($at) {
                $this->render("plant-{$plant->slug}", fn () => ShareImageService::plant($plant, $at));
            });
        }

        return self::SUCCESS;
    }

    protected function render(string $label, callable $callback): void
    {
        $this->components->task("og/{$label}.png", $callback);
    }
}
