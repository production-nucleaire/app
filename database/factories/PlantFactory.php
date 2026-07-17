<?php

namespace Database\Factories;

use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plant>
 */
class PlantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'latitude' => fake()->randomFloat(5, 43, 51),
            'longitude' => fake()->randomFloat(5, -1, 7),
            'cooling_type' => 'RIVER',
            'cooling_place' => fake()->word(),
        ];
    }
}
