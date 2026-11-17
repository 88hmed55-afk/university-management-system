<?php

namespace Database\Factories;

use App\Models\AdmissionCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdmissionCycle>
 */
class AdmissionCycleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CYCLE-####'),
            'name' => fake()->bothify('دورة قبول ???'),
        ];
    }
}
