<?php

namespace Database\Factories;

use App\Models\CourseSection;
use App\Models\SectionMeeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SectionMeeting>
 */
class SectionMeetingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_section_id' => CourseSection::factory(),
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:30',
            'valid_from' => '2040-01-01',
        ];
    }
}
