<?php

use App\Models\Plant;
use App\Models\Reactor;
use App\Models\Record;
use Illuminate\Support\Collection;

/**
 * Build a reactor with an in-memory latest record (no DB touched).
 */
function reactorWithLatest(float $netPowerMw, ?int $latestValue): Reactor
{
    $reactor = new Reactor(['net_power_mw' => $netPowerMw]);

    if ($latestValue !== null) {
        $record = new Record(['value' => $latestValue]);
        $record->setRelation('reactor', $reactor);
        $reactor->setRelation('latestRecord', $record);
    } else {
        $reactor->setRelation('latestRecord', null);
    }

    return $reactor;
}

describe('Record::percent_value', function () {
    it('computes the rounded-up percentage of net power', function () {
        $reactor = new Reactor(['net_power_mw' => 1000]);
        $record = new Record(['value' => 901]);
        $record->setRelation('reactor', $reactor);

        // ceil(901 / 1000 * 100) = ceil(90.1) = 91
        expect($record->percent_value)->toBe(91);
    });

    it('clamps values above net power to 100', function () {
        $reactor = new Reactor(['net_power_mw' => 1000]);
        $record = new Record(['value' => 1200]);
        $record->setRelation('reactor', $reactor);

        expect($record->percent_value)->toBe(100);
    });

    it('clamps negative production to 0', function () {
        $reactor = new Reactor(['net_power_mw' => 1000]);
        $record = new Record(['value' => -50]);
        $record->setRelation('reactor', $reactor);

        expect($record->percent_value)->toBe(0);
    });
});

describe('Plant production accessors', function () {
    it('counts a reactor as active only at >= 5% output', function () {
        $plant = new Plant;
        $plant->setRelation('reactors', new Collection([
            reactorWithLatest(1000, 900), // 90% -> active
            reactorWithLatest(1000, 40),  // 4%  -> inactive
            reactorWithLatest(1000, 50),  // 5%  -> active (boundary)
            reactorWithLatest(1000, null), // no record -> inactive
        ]));

        expect($plant->active_reactors_count)->toBe(2);
    });

    it('sums latest production and total capacity across reactors', function () {
        $plant = new Plant;
        $plant->setRelation('reactors', new Collection([
            reactorWithLatest(1000, 900),
            reactorWithLatest(1500, 300),
            reactorWithLatest(1000, null), // contributes 0 to latest, 1000 to total
        ]));

        expect($plant->latest_production_mw)->toBe(1200.0)
            ->and($plant->total_production_mw)->toBe(3500.0)
            // 1200 / 3500 * 100
            ->and(round($plant->percent_value, 2))->toBe(34.29);
    });

    it('reports 0% when the plant has no capacity', function () {
        $plant = new Plant;
        $plant->setRelation('reactors', new Collection);

        expect($plant->percent_value)->toBe(0.0);
    });
});
