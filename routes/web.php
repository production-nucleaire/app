<?php

use App\Livewire\PlantMap;
use Illuminate\Support\Facades\Route;

Route::get('/', PlantMap::class)
    ->name('welcome');
