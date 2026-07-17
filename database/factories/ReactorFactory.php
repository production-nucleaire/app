<?php

namespace Database\Factories;

use App\Models\Plant;
use App\Models\Reactor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Reactor>
 */
class ReactorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'plant_id' => Plant::factory(),
            'name' => fake()->city().'-'.fake()->numberBetween(1, 6),
            'eic_code' => strtoupper(Str::random(16)),
            'reactor_index' => 1,
            'stage' => 'CP1',
            'thermal_power_mw' => 2785,
            'raw_power_mw' => 951,
            'net_power_mw' => 900,
            'build_start_date' => '1977-01-01',
            'first_reaction_date' => '1981-05-20',
            'grid_link_date' => '1981-06-12',
            'exploitation_start_date' => '1981-12-01',
            'mox_authorization_date' => '1997-09-02',
            'cooling_tower_count' => 2,
        ];
    }
}
