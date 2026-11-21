<?php

namespace Database\Factories;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestType;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'request_no' => fake()->unique()->bothify('REQ-####'),
            'service_request_type_id' => ServiceRequestType::factory(),
            'requester_type' => Student::class,
            'requester_id' => Student::factory(),
            'description' => fake()->sentence(),
            'status' => 'submitted',
            'submitted_at' => now(),
        ];
    }
}
