<?php

namespace Database\Factories;

use App\Models\AssessmentComponent;
use App\Models\AssessmentScore;
use App\Models\CourseEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentScore>
 */
class AssessmentScoreFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_enrollment_id' => CourseEnrollment::factory(),
            'assessment_component_id' => AssessmentComponent::factory(),
        ];
    }
}
