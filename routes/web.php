<?php

use App\Models\Plant;
use App\Livewire\History;
use App\Livewire\PlantMap;
use Illuminate\Http\Request;
use App\Livewire\ReactorTable;
use Illuminate\Support\Carbon;
use App\Services\ReactorSeries;
use App\Services\NationalSeries;
use Illuminate\Support\Facades\Route;

Route::get('/', PlantMap::class)
    ->name('home');

Route::get('/historique', History::class)
    ->name('history');

Route::get('/tableau', ReactorTable::class)
    ->name('table');

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

// Lazy "load older" data source for the national Historique chart.
Route::get('/api/history', function (Request $request) {
    $len = (int) $request->query('len', 10);
    $end = Carbon::createFromTimestamp((int) $request->query('end', now()->timestamp));
    $start = Carbon::createFromTimestamp((int) $request->query('start', $end->copy()->subDays(30)->timestamp));

    if ($start->greaterThanOrEqualTo($end)) {
        return response()->json([]);
    }

    return response()->json(NationalSeries::between($start, $end, $len));
})->name('api.history');

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
