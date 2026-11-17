<?php

namespace Database\Factories;

use App\Models\ApplicantEvent;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicantEvent>
 */
class ApplicantEventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'event_type' => 'submitted',
        ];
    }
}
