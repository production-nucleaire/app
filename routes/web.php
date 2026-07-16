<?php

use App\Livewire\History;
use App\Livewire\PlantMap;
use App\Models\Plant;
use App\Services\ReactorSeries;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

Route::get('/', PlantMap::class)
    ->name('home');

Route::get('/historique', History::class)
    ->name('history');

// Lazy "load older" data source for the interactive per-tranche chart.
Route::get('/api/plants/{plant:slug}/records', function (Plant $plant, Request $request) {
    $end = Carbon::createFromTimestamp((int) $request->query('end', now()->timestamp));
    $start = Carbon::createFromTimestamp((int) $request->query('start', $end->copy()->subDays(30)->timestamp));

    // Guard against pathological ranges.
    if ($start->greaterThanOrEqualTo($end) || $start->diffInDays($end) > 400) {
        return response()->json([]);
    }

    return response()->json(
        ReactorSeries::between($plant->load('reactors'), $start, $end)
    );
})->name('api.plant.records');

Route::get('/a-propos', fn () => view('welcome'))
    ->name('welcome');

Route::get('/{slug}', PlantMap::class)
    ->where('slug', '[a-z0-9\-]+')
    ->name('plant');

Route::get('/{slug}/tranche/{reactor}', PlantMap::class)
    ->where([
        'slug' => '[a-z0-9\-]+',
        'reactor' => '[0-9]+',
    ])
    ->name('reactor');
