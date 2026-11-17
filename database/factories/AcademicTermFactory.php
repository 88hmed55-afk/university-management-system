<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'code' => fake()->unique()->bothify('T-####'),
            'name' => fake()->bothify('فصل ???'),
            'start_date' => '2040-01-01',
            'end_date' => '2040-06-30',
        ];
    }
}
