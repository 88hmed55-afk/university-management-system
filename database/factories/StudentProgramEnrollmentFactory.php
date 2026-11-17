<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentProgramEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProgramEnrollment>
 */
class StudentProgramEnrollmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_program_id' => AcademicProgram::factory(),
            'academic_year_id' => AcademicYear::factory(),
        ];
    }
}
