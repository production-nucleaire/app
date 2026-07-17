<?php

use App\Livewire\History;
use App\Livewire\PlantMap;
use App\Models\Plant;
use App\Models\Reactor;
use App\Models\Record;
use App\Services\NationalStats;

/**
 * Build a plant with reactors and a short run of hourly records ending "now".
 */
function seedPlant(string $name, string $slug, array $reactorPowers, array $latestValues): Plant
{
    $plant = Plant::create([
        'name' => $name,
        'latitude' => 47.0,
        'longitude' => 2.5,
        'cooling_type' => 'RIVER',
        'cooling_place' => 'Loire',
    ]);
    // slug is guarded (not in $fillable), so set it explicitly.
    $plant->forceFill(['slug' => $slug])->save();

    foreach ($reactorPowers as $i => $power) {
        $reactor = Reactor::create([
            'name' => "$name $i",
            'eic_code' => strtoupper($slug).'-'.$i,
            'plant_id' => $plant->id,
            'reactor_index' => $i + 1,
            'stage' => 'CP1',
            'thermal_power_mw' => $power * 3,
            'raw_power_mw' => $power + 20,
            'net_power_mw' => $power,
            'build_start_date' => '1975-01-01',
            'first_reaction_date' => '1979-06-01',
            'grid_link_date' => '1980-01-01',
            'exploitation_start_date' => '1981-01-01',
            'mox_authorization_date' => '1990-01-01',
            'cooling_tower_count' => 2,
        ]);

        // 48 hourly records so the chart window has data.
        for ($h = 47; $h >= 0; $h--) {
            Record::create([
                'reactor_id' => $reactor->id,
                'date' => now()->subHours($h),
                'value' => $h === 0 ? $latestValues[$i] : $power,
            ]);
        }
    }

    return $plant;
}

beforeEach(function () {
    cache()->flush();
});

it('renders the national view', function () {
    seedPlant('Testville', 'centrale-de-test', [900, 900], [880, 10]);

    $this->get('/')
        ->assertOk()
        ->assertSee('électronucléaire')
        ->assertSee('CENTRALES')
        ->assertSee('Testville');
});

it('renders the plant (par centrale) view with the chart', function () {
    seedPlant('Testville', 'centrale-de-test', [900, 900], [880, 10]);

    $this->get('/centrale-de-test')
        ->assertOk()
        ->assertSee('Testville')
        ->assertSee('Production horaire par tranche')
        ->assertSee('Tranche 1');
});

it('renders the historique view', function () {
    seedPlant('Testville', 'centrale-de-test', [900, 900], [880, 10]);

    $this->get('/historique')
        ->assertOk()
        ->assertSee('Production nucléaire nationale')
        ->assertSee('FACTEUR DE CHARGE MOYEN');
});

it('respects a custom du/au date range on historique', function () {
    seedPlant('Testville', 'centrale-de-test', [900, 900], [880, 10]);

    $from = now()->subHours(24)->format('Y-m-d');
    $to = now()->format('Y-m-d');

    $this->get("/historique?du={$from}&au={$to}")
        ->assertOk()
        ->assertSee('Production nucléaire nationale');

    // The custom window (<= 2 days) resolves to hourly points with data.
    $component = new History;
    $component->from = $from;
    $component->to = $to;

    expect($component->points())->not->toBeEmpty();
});

it('opens a plant preview and nudges the nap easter egg for a consuming plant', function () {
    seedPlant('Napville', 'centrale-napville', [900, 900], [-3, 0]);

    $plant = Plant::firstWhere('slug', 'centrale-napville');

    Livewire\Livewire::test(PlantMap::class)
        ->call('openPreview', $plant->id)
        ->assertSet('previewPlantId', $plant->id)
        ->assertDispatched('easter-nap');
});

it('computes national stats', function () {
    seedPlant('Testville', 'centrale-de-test', [900, 900], [880, 10]);

    $stats = NationalStats::get();

    expect($stats['total_reactors'])->toBe(2)
        ->and($stats['coupled'])->toBe(1)          // only the 880 MW unit is >= 5%
        ->and($stats['injected_mw'])->toBe(890)    // 880 + 10
        ->and($stats['capacity_mw'])->toBe(1800)
        ->and($stats['top_plant_slug'])->toBe('centrale-de-test')
        ->and($stats['spark24h'])->not->toBeEmpty();
});

it('serves per-tranche records from the lazy-load endpoint', function () {
    seedPlant('Testville', 'centrale-de-test', [900, 900], [880, 10]);

    $end = now()->timestamp;
    $start = now()->subHours(48)->timestamp;

    $response = $this->getJson("/api/plants/centrale-de-test/records?start={$start}&end={$end}")
        ->assertOk();

    $data = $response->json();

    expect($data)->toHaveCount(2)
        ->and($data[0]['name'])->toBe('Tranche 1')
        ->and($data[0]['data'])->not->toBeEmpty();
});
