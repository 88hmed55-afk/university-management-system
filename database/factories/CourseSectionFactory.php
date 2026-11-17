<?php

namespace Database\Factories;

use App\Models\CourseOffering;
use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseSection>
 */
class CourseSectionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_offering_id' => CourseOffering::factory(),
            'code' => fake()->unique()->bothify('SEC-####'),
        ];
    }
}
