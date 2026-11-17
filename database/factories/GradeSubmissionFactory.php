<?php

namespace Database\Factories;

use App\Models\CourseSection;
use App\Models\GradeSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeSubmission>
 */
class GradeSubmissionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_section_id' => CourseSection::factory(),
            'submitted_by' => User::factory(),
            'status' => 'submitted',
            'submitted_at' => now(),
        ];
    }
}
