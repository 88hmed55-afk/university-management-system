<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSession>
 */
class AttendanceSessionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_section_id' => CourseSection::factory(),
            'session_date' => '2040-01-01',
        ];
    }
}
