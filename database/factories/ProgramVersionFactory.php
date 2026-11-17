<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use App\Models\ProgramVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramVersion>
 */
class ProgramVersionFactory extends Factory
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
