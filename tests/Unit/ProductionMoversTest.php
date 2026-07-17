<?php

use App\Models\Plant;
use App\Models\Reactor;
use App\Models\Record;
use App\Services\ProductionMovers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function seedPlantWithHours(string $slug, ?int $prev, int $current): Plant
{
    $plant = Plant::factory()->create(['name' => ucfirst($slug), 'slug' => $slug]);
    $reactor = Reactor::factory()->for($plant)->create(['reactor_index' => 1, 'net_power_mw' => 1300]);

    if ($prev !== null) {
        Record::factory()->for($reactor)->create(['value' => $prev, 'date' => now()->subHour()]);
    }
    Record::factory()->for($reactor)->create(['value' => $current, 'date' => now()]);

    return $plant;
}

it('ranks plants by absolute hourly delta and drops flat / single-bucket plants', function () {
    seedPlantWithHours('alpha', 800, 1200);    // +400
    seedPlantWithHours('bravo', 1000, 400);    // -600  (biggest)
    seedPlantWithHours('charlie', 500, 500);   //    0  (dropped)
    seedPlantWithHours('delta', null, 700);    // one bucket only (dropped)

    $top = ProductionMovers::top(2);

    expect($top)->toHaveCount(2)
        ->and($top[0]['plant']->slug)->toBe('bravo')
        ->and($top[0]['delta'])->toBe(-600)
        ->and($top[0]['current'])->toBe(400)
        ->and($top[1]['plant']->slug)->toBe('alpha')
        ->and($top[1]['delta'])->toBe(400);
});

it('returns an empty list when nothing moved', function () {
    seedPlantWithHours('alpha', 500, 500);

    expect(ProductionMovers::top(2))->toBe([]);
});
