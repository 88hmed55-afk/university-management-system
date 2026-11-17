<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicProgram>
 */
class AcademicProgramFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PROG-####'),
            'name' => fake()->bothify('برنامج ???'),
            'primary_college_id' => College::factory(),
        ];
    }
}
