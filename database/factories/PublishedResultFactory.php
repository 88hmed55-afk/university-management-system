<?php

namespace Database\Factories;

use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\PublishedResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PublishedResult>
 */
class PublishedResultFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_section_id' => CourseSection::factory(),
            'course_enrollment_id' => CourseEnrollment::factory(),
            'payload' => ['scores' => []],
            'published_by' => User::factory(),
            'published_at' => now(),
            'decision_reference' => fake()->unique()->bothify('PUB-####'),
        ];
    }
}
