<?php

namespace Database\Factories;

use App\Models\ServiceRequestType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequestType>
 */
class ServiceRequestTypeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SVC-####'),
            'name' => fake()->bothify('خدمة ???'),
            'description' => fake()->sentence(),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
