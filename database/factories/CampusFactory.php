<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campus>
 */
class CampusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'code' => fake()->unique()->bothify('CAMPUS-???'),
            'name' => fake()->bothify('حرم تجريبي ???'),
        ];
    }
}
