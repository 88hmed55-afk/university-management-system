<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\FeePolicy;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentInvoice>
 */
class StudentInvoiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'invoice_no' => fake()->unique()->bothify('INV-####'),
            'student_id' => Student::factory(),
            'fee_policy_id' => FeePolicy::factory(),
            'currency_id' => Currency::factory(),
            'status' => 'issued',
            'total_amount' => '100.00',
            'created_by' => User::factory(),
            'issued_at' => now(),
            'issued_by' => User::factory(),
        ];
    }
}
