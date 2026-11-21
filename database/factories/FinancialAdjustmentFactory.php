<?php

namespace Database\Factories;

use App\Models\FinancialAdjustment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialAdjustment>
 */
class FinancialAdjustmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'adjustment_no' => fake()->unique()->bothify('ADJ-####'),
            'student_id' => Student::factory(),
            'direction' => 'credit',
            'amount' => '10.00',
            'reason' => 'تسوية اختبار',
            'created_by' => User::factory(),
        ];
    }
}
