<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmploymentAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentAssignment>
 */
class EmploymentAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'valid_from' => '2026-01-01',
            'decision_reference' => fake()->unique()->bothify('DEC-####'),
        ];
    }
}
