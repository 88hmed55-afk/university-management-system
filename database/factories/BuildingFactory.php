<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Campus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campus_id' => Campus::factory(),
            'code' => fake()->unique()->bothify('BLD-???'),
            'name' => fake()->bothify('مبنى تجريبي ???'),
        ];
    }
}
