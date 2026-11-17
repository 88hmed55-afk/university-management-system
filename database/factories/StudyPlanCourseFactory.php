<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\StudyPlanCourse;
use App\Models\StudyPlanVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyPlanCourse>
 */
class StudyPlanCourseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'study_plan_version_id' => StudyPlanVersion::factory(),
            'course_id' => Course::factory(),
        ];
    }
}
