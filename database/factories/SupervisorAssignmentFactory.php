<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PostgraduateRecord;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisorAssignment>
 */
class SupervisorAssignmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'postgraduate_record_id' => PostgraduateRecord::factory(),
            'employee_id' => Employee::factory(),
            'role_code' => 'supervisor',
            'valid_from' => fake()->date(),
            'status' => 'nominated',
            'nominated_by' => User::factory(),
            'approved_by' => User::factory(),
        ];
    }
}
