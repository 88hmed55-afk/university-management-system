<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseVersion>
 */
class CourseVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'version_no' => fake()->unique()->numberBetween(1, 1000),
            'status' => 'draft',
        ];
    }
}
