<?php

namespace Database\Factories;

use App\Models\AssessmentComponent;
use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentComponent>
 */
class AssessmentComponentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_section_id' => CourseSection::factory(),
            'code' => fake()->unique()->bothify('AC-####'),
            'name' => fake()->bothify('عنصر ???'),
        ];
    }
}
