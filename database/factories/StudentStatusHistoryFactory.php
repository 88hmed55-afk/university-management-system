<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentStatusHistory>
 */
class StudentStatusHistoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'to_status' => 'active',
            'reason' => fake()->sentence(),
            'effective_date' => '2040-01-01',
            'decision_reference' => fake()->unique()->bothify('ST-####'),
        ];
    }
}
