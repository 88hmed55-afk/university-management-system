<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use App\Models\College;
use App\Models\ProgramCollegeParticipation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramCollegeParticipation>
 */
class ProgramCollegeParticipationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'academic_program_id' => AcademicProgram::factory(),
            'college_id' => College::factory(),
            'valid_from' => '2040-01-01',
            'decision_reference' => fake()->unique()->bothify('PART-####'),
        ];
    }
}
