<?php

use App\Models\Plant;
use App\Models\Reactor;
use App\Models\Record;
use App\Services\ShareImageService;
use App\Support\OgSvg;

/**
 * Seed one plant (2 reactors, one high load, one offline) plus a second plant
 * so the national card has multiple map dots.
 */
function seedFleet(): Plant
{
    $plant = Plant::factory()->create([
        'name' => 'Dampierre-en-Burly',
        'slug' => 'dampierre',
        'latitude' => 47.7331,
        'longitude' => 2.5169,
        'cooling_place' => 'Loire',
    ]);

    $r1 = Reactor::factory()->for($plant)->create(['reactor_index' => 1, 'net_power_mw' => 900, 'stage' => 'CP1']);
    $r2 = Reactor::factory()->for($plant)->create(['reactor_index' => 2, 'net_power_mw' => 900, 'stage' => 'CP1']);
    Record::factory()->for($r1)->create(['value' => 850, 'date' => now()]);
    Record::factory()->for($r2)->create(['value' => 0, 'date' => now()]);

    $other = Plant::factory()->create(['name' => 'Golfech', 'slug' => 'golfech', 'latitude' => 44.107, 'longitude' => 0.845]);
    $r3 = Reactor::factory()->for($other)->create(['reactor_index' => 1, 'net_power_mw' => 1300]);
    Record::factory()->for($r3)->create(['value' => 1200, 'date' => now()]);

    return $plant->fresh();
}

it('renders the national view with live figures and no leaked directives', function () {
    seedFleet();

    $html = view('og.national', ShareImageService::nationalData(now()))->render();

    expect($html)
        ->toContain('LE PARC NUCLÉAIRE FRANÇAIS, EN DIRECT')
        ->toContain('GW')
        ->toContain('<svg')          // France map
        ->not->toContain('@if')      // inline directive compiled, not leaked
        ->not->toContain('@endif');
});

it('renders the plant view with descriptor, chips and chart', function () {
    $plant = seedFleet();

    $html = view('og.plant', ShareImageService::plantData($plant, now()))->render();

    expect($html)
        ->toContain('Dampierre-en-Burly')
        ->toContain('CENTRALE NUCLÉAIRE')
        ->toContain('LOIRE')
        ->toContain('2 × 900 MW')
        ->toContain('T1 · ')
        ->toContain('T2 · maintenance');   // second reactor at 0 MW
});

it('renders the default brand card', function () {
    seedFleet();

    $html = view('og.default', ShareImageService::defaultData(now()))->render();

    expect($html)
        ->toContain('électronucléaire')
        ->toContain('centrales')
        ->toContain('tranches');
});

it('switches to the dark theme at night and light during the day', function () {
    seedFleet();

    $night = view('og.national', ShareImageService::nationalData(now()->setTime(3, 0)))->render();
    $day = view('og.national', ShareImageService::nationalData(now()->setTime(14, 0)))->render();

    expect($night)->toContain('data-theme="dark"');
    expect($day)->toContain('data-theme="light"');
});

it('projects plant dots onto the France outline', function () {
    $svg = OgSvg::franceMap([
        ['lon' => 2.13, 'lat' => 51.01, 'status' => 'coupled'],
        ['lon' => 0.845, 'lat' => 44.107, 'status' => 'neg'],
    ]);

    expect($svg)
        ->toContain('<path')     // land outline
        ->toContain('<circle')   // dots
        ->toContain('#0d8a4f')   // coupled colour
        ->toContain('#b5471d');  // consommatrice colour
});

it('rasterises a 1200x630 PNG (+@2x) via Browsershot', function () {
    $chrome = config('services.browsershot.chrome_path') ?: '/usr/bin/google-chrome';
    if (! is_file($chrome)) {
        $this->markTestSkipped('Headless Chrome not available.');
    }

    // Render a factory-slug plant card so we never clobber the canonical
    // national/default images in dev storage.
    $plant = seedFleet();
    ShareImageService::plant($plant, now());

    $png = storage_path('app/public/og/plant-'.$plant->slug.'.png');
    $png2x = storage_path('app/public/og/plant-'.$plant->slug.'@2x.png');

    try {
        expect(is_file($png))->toBeTrue();
        expect(getimagesize($png))->toMatchArray([0 => 1200, 1 => 630]);
        expect(getimagesize($png2x))->toMatchArray([0 => 2400, 1 => 1260]);
    } finally {
        @unlink($png);
        @unlink($png2x);
    }
})->skip(fn () => (getenv('CI') !== false), 'Skips Browsershot render on CI.');
