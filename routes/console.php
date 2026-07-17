<?php

use Illuminate\Support\Facades\Schedule;

if (app()->environment() === 'production') {
    Schedule::command('app:import-rte-data --unofficial')->hourly();
    // Schedule::command('app:import-rte-data')->dailyAt('00:00');

    // Post to Bluesky 10 min past the hour, once the import above has refreshed
    // the data + national card. withoutOverlapping guards against a slow run.
    Schedule::command('app:post-live-production-to-bluesky')
        ->hourlyAt(10)
        ->withoutOverlapping();
}
