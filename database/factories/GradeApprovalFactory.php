<?php

namespace Database\Factories;

use App\Models\GradeApproval;
use App\Models\GradeSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeApproval>
 */
class GradeApprovalFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'grade_submission_id' => GradeSubmission::factory(),
            'decision' => 'approved',
            'approver_id' => User::factory(),
            'decision_reference' => fake()->unique()->bothify('GA-####'),
        ];
    }
}
