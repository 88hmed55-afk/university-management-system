<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentTranscript;
use App\Models\StudyPlanVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentTranscript>
 */
class StudentTranscriptFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'study_plan_version_id' => StudyPlanVersion::factory(),
            'payload' => ['courses' => []],
            'generated_by' => User::factory(),
            'generated_at' => now(),
            'decision_reference' => fake()->unique()->bothify('TR-####'),
        ];
    }
}
