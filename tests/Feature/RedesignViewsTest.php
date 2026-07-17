<?php

use App\Livewire\History;
use App\Livewire\PlantMap;
use App\Livewire\ReactorTable;
use App\Models\Plant;
use App\Models\Reactor;
use App\Models\Record;
use App\Services\NationalMonthly;
use App\Services\NationalStats;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

it('renders the reactor table (3a) grouped by plant', function () {
    seedPlant('Testville', 'centrale-de-test', [900, 900], [880, 10]);

    $this->get('/tableau')
        ->assertOk()
        ->assertSee('Testville')
        ->assertSee('Tableau')
        ->assertSee('Toutes · 2')
        ->assertSee('couplée · pleine puissance');
});

it('filters reactor rows by coupled / stopped', function () {
    seedPlant('Testville', 'centrale-de-test', [900, 900], [880, 10]);

    $unitsFor = function (string $filter) {
        $c = new ReactorTable;
        $c->filter = $filter;

        return collect($c->groups())->sum(fn ($g) => count($g['units']));
    };

    expect($unitsFor('toutes'))->toBe(2)
        ->and($unitsFor('couplees'))->toBe(1)  // only the 880 MW unit is >= 5%
        ->and($unitsFor('arret'))->toBe(1);
});

it('exports the reactor table as CSV', function () {
    seedPlant('Testville', 'centrale-de-test', [900, 900], [880, 10]);

    $response = (new ReactorTable)->exportCsv();
    expect($response)->toBeInstanceOf(StreamedResponse::class);

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    $lines = array_values(array_filter(explode("\n", trim($csv))));
    expect($lines)->toHaveCount(3)                     // header + 2 reactors
        ->and($lines[0])->toContain('Centrale', 'Production (MW)')
        ->and($csv)->toContain('Testville');
});

it('renders the history table (3b) view', function () {
    seedPlant('Testville', 'centrale-de-test', [900, 900], [880, 10]);

    $this->get('/historique?vue=tableau')
        ->assertOk()
        ->assertSee('PÉRIODE')
        ->assertSee('ÉNERGIE (TWh)');
});

it('groups monthly history in the app timezone without leaking across month boundaries', function () {
    // A month-boundary bucket (1st at 00:00 Paris) must stay in its own month, not slip into
    // the previous one when the timestamp is read back — guards the createFromTimestamp tz fix.
    $plant = Plant::create([
        'name' => 'Tzville', 'latitude' => 47.0, 'longitude' => 2.5,
        'cooling_type' => 'RIVER', 'cooling_place' => 'Loire',
    ]);
    $reactor = Reactor::create([
        'name' => 'Tzville 1', 'eic_code' => 'TZ-1', 'plant_id' => $plant->id,
        'reactor_index' => 1, 'stage' => 'CP1', 'thermal_power_mw' => 2700,
        'raw_power_mw' => 920, 'net_power_mw' => 900, 'build_start_date' => '1975-01-01',
        'first_reaction_date' => '1979-06-01', 'grid_link_date' => '1980-01-01',
        'exploitation_start_date' => '1981-01-01', 'mox_authorization_date' => '1990-01-01',
        'cooling_tower_count' => 2,
    ]);

    foreach (['2025-07-01 00:00:00', '2025-07-02 00:00:00', '2025-07-03 00:00:00'] as $date) {
        Record::create(['reactor_id' => $reactor->id, 'date' => $date, 'value' => 800]);
    }

    $table = NationalMonthly::table(Carbon::parse('2025-07-01'), Carbon::parse('2025-07-31 23:00:00'));

    $year = collect($table)->firstWhere('year', 2025);
    expect($year)->not->toBeNull()
        ->and($year['rows'])->toHaveCount(1); // July only — no phantom June bucket
});

it('buckets the history table by the chosen granularity', function () {
    $plant = Plant::create([
        'name' => 'Grainville', 'latitude' => 47.0, 'longitude' => 2.5,
        'cooling_type' => 'RIVER', 'cooling_place' => 'Loire',
    ]);
    $reactor = Reactor::create([
        'name' => 'Grainville 1', 'eic_code' => 'GR-1', 'plant_id' => $plant->id,
        'reactor_index' => 1, 'stage' => 'CP1', 'thermal_power_mw' => 2700,
        'raw_power_mw' => 920, 'net_power_mw' => 900, 'build_start_date' => '1975-01-01',
        'first_reaction_date' => '1979-06-01', 'grid_link_date' => '1980-01-01',
        'exploitation_start_date' => '1981-01-01', 'mox_authorization_date' => '1990-01-01',
        'cooling_tower_count' => 2,
    ]);

    // Three consecutive days in a single ISO week of July 2025.
    foreach (['2025-07-07 00:00:00', '2025-07-08 00:00:00', '2025-07-09 00:00:00'] as $date) {
        Record::create(['reactor_id' => $reactor->id, 'date' => $date, 'value' => 800]);
    }

    $start = Carbon::parse('2025-07-01');
    $end = Carbon::parse('2025-07-31 23:00:00');

    $rowsFor = fn (string $grain) => collect(NationalMonthly::table($start, $end, $grain))
        ->firstWhere('year', 2025)['rows'];

    expect($rowsFor('jour'))->toHaveCount(3)     // one row per day
        ->and($rowsFor('semaine'))->toHaveCount(1) // all in one ISO week
        ->and($rowsFor('mois'))->toHaveCount(1)    // all in July
        ->and($rowsFor('annee'))->toHaveCount(0);  // year is the leaf
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
