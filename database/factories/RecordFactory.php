<?php

namespace Database\Factories;

use App\Models\Reactor;
use App\Models\Record;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Record>
 */
class RecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reactor_id' => Reactor::factory(),
            'date' => now(),
            'value' => fake()->numberBetween(0, 900),
        ];
    }
}
