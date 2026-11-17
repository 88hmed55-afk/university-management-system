<?php

namespace Database\Factories;

use App\Models\AdmissionCycle;
use App\Models\Application;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'admission_cycle_id' => AdmissionCycle::factory(),
            'person_id' => Person::factory(),
            'application_no' => fake()->unique()->numerify('APP########'),
            'idempotency_key' => fake()->unique()->uuid(),
        ];
    }
}
