<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use App\Models\PostgraduateRecord;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostgraduateRecord>
 */
class PostgraduateRecordFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'record_no' => fake()->unique()->bothify('PG-####'),
            'student_id' => Student::factory(),
            'program_id' => AcademicProgram::factory(),
            'degree_code' => 'masters',
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
