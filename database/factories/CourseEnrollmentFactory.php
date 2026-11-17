<?php

namespace Database\Factories;

use App\EnrollmentStatus;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseEnrollment>
 */
class CourseEnrollmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'course_section_id' => CourseSection::factory(),
            'status' => EnrollmentStatus::Enrolled,
            'enrolled_at' => now(),
        ];
    }
}
