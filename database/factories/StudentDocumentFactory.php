<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDocument>
 */
class StudentDocumentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'document_id' => Document::factory(),
        ];
    }
}
