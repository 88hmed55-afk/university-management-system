<?php

namespace Database\Factories;

use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CourseEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'attendance_session_id' => AttendanceSession::factory(),
            'course_enrollment_id' => CourseEnrollment::factory(),
            'status' => AttendanceStatus::Present,
            'recorded_at' => now(),
        ];
    }
}
