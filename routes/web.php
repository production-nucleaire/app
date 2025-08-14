<?php

use App\Livewire\PlantMap;
use Illuminate\Support\Facades\Route;

Route::get('/', PlantMap::class)
    ->name('home');

Route::get('/a-propos', fn() => view('welcome'))
    ->name('welcome');

Route::get('/{slug}', PlantMap::class)
    ->where('slug', '[a-z0-9\-]+')
    ->name('plant');

Route::get('/{slug}/tranche/{reactor}', PlantMap::class)
    ->where([
        'slug' => '[a-z0-9\-]+',
        'reactor' => '[0-9]+'
    ])
    ->name('reactor');
