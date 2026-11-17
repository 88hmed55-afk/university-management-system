<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentPlanAssignment;
use App\Models\StudyPlanVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentPlanAssignment>
 */
class StudentPlanAssignmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'study_plan_version_id' => StudyPlanVersion::factory(),
            'effective_from' => '2040-01-01',
            'decision_reference' => fake()->unique()->bothify('PLAN-####'),
        ];
    }
}
