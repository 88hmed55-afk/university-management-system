<?php

namespace Database\Factories;

use App\Models\ServiceRequestType;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowStep>
 */
class WorkflowStepFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'service_request_type_id' => ServiceRequestType::factory(),
            'version_no' => 1,
            'step_no' => 1,
            'name' => fake()->bothify('خطوة ???'),
            'role_code' => 'registration_officer',
            'scope_code' => 'campus',
            'status' => 'draft',
        ];
    }
}
