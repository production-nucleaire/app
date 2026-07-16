<?php

use App\Models\Plant;
use App\Models\Reactor;
use App\Models\Record;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function makeReactor(string $eic = 'EIC-TEST-1'): Reactor
{
    $plant = Plant::create([
        'name' => 'Test Plant',
        'slug' => 'test-plant',
        'latitude' => 46.0,
        'longitude' => 2.0,
    ]);

    return Reactor::create([
        'name' => 'Test Reactor 1',
        'eic_code' => $eic,
        'plant_id' => $plant->id,
        'reactor_index' => 1,
        'stage' => 'P4',
        'thermal_power_mw' => 3800,
        'raw_power_mw' => 1350,
        'net_power_mw' => 1300,
        'build_start_date' => '1980-01-01',
        'first_reaction_date' => '1985-01-01',
        'grid_link_date' => '1985-06-01',
        'exploitation_start_date' => '1986-01-01',
        'mox_authorization_date' => '2000-01-01',
        'cooling_tower_count' => 2,
    ]);
}

function fakeUnofficial(int $group): void
{
    Http::fake([
        'www.services-rte.com/*' => Http::response([
            'values' => [
                ['date' => '2026-07-10T06:00:00+02:00', 'group' => $group],
                ['date' => '2026-07-10T07:00:00+02:00', 'group' => $group + 10],
            ],
        ]),
    ]);
}

it('imports unofficial data for a reactor', function () {
    $reactor = makeReactor();
    fakeUnofficial(800);

    $this->artisan('app:import-rte-data', [
        '--unofficial' => true,
        '--eic' => $reactor->eic_code,
        '--start' => '2026-07-10',
        '--end' => '2026-07-10',
    ])->assertSuccessful();

    expect(Record::count())->toBe(2)
        ->and(Record::where('reactor_id', $reactor->id)->orderBy('date')->pluck('value')->all())
        ->toBe([800, 810]);
});

it('is idempotent and updates in place on re-import (upsert, no duplicates)', function () {
    $reactor = makeReactor();

    // Each command run makes one HTTP call; the sequence feeds run 1 then run 2.
    Http::fakeSequence('www.services-rte.com/*')
        ->push(['values' => [
            ['date' => '2026-07-10T06:00:00+02:00', 'group' => 800],
            ['date' => '2026-07-10T07:00:00+02:00', 'group' => 810],
        ]])
        ->push(['values' => [
            ['date' => '2026-07-10T06:00:00+02:00', 'group' => 950],
            ['date' => '2026-07-10T07:00:00+02:00', 'group' => 960],
        ]]);

    $args = [
        '--unofficial' => true,
        '--eic' => $reactor->eic_code,
        '--start' => '2026-07-10',
        '--end' => '2026-07-10',
    ];

    $this->artisan('app:import-rte-data', $args)->assertSuccessful();
    expect(Record::count())->toBe(2);

    // Same timestamps, new values -> rows are updated, not duplicated.
    $this->artisan('app:import-rte-data', $args)->assertSuccessful();

    expect(Record::count())->toBe(2)
        ->and(Record::orderBy('date')->pluck('value')->all())->toBe([950, 960]);
});
