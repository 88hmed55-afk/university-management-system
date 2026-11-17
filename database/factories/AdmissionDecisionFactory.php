<?php

namespace Database\Factories;

use App\Models\AdmissionDecision;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdmissionDecision>
 */
class AdmissionDecisionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'decision' => 'approved',
            'decided_at' => now(),
            'reference' => fake()->unique()->bothify('REF-####'),
        ];
    }
}
