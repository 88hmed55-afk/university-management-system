<?php

namespace Database\Factories;

use App\Models\GraduationAudit;
use App\Models\Student;
use App\Models\StudyPlanVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GraduationAudit>
 */
class GraduationAuditFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'study_plan_version_id' => StudyPlanVersion::factory(),
            'payload' => ['courses' => [], 'missing' => []],
            'decision_reference' => fake()->unique()->bothify('GAUD-####'),
            'audited_by' => User::factory(),
            'audited_at' => now(),
        ];
    }
}
