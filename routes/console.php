<?php

use Illuminate\Support\Facades\Schedule;

if ('production' === app()->environment()) {
    Schedule::command('app:import-rte-data --unofficial')->hourly();
    // Schedule::command('app:import-rte-data')->dailyAt('00:00');
}
