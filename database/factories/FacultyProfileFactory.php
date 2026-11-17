<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\FacultyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacultyProfile>
 */
class FacultyProfileFactory extends Factory
{
    /**
     * Rank and specialization codes stay empty until the university approves them (P-015).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
        ];
    }
}
