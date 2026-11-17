<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use App\Models\StudyPlanVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyPlanVersion>
 */
class StudyPlanVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'academic_program_id' => AcademicProgram::factory(),
            'version_no' => fake()->unique()->numberBetween(1, 1000),
            'status' => 'draft',
        ];
    }
}
