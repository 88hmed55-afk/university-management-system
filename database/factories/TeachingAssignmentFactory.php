<?php

namespace Database\Factories;

use App\Models\CourseSection;
use App\Models\Employee;
use App\Models\TeachingAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeachingAssignment>
 */
class TeachingAssignmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_section_id' => CourseSection::factory(),
            'employee_id' => Employee::factory(),
            'valid_from' => '2040-01-01',
            'decision_reference' => fake()->unique()->bothify('TA-####'),
        ];
    }
}
