<?php

namespace Database\Factories;

use App\Models\RoomFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomFeature>
 */
class RoomFeatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('FEATURE-???'),
        ];
    }
}
