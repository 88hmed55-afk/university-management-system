<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentPayment>
 */
class StudentPaymentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'payment_no' => fake()->unique()->bothify('PAY-####'),
            'student_id' => Student::factory(),
            'amount' => '100.00',
            'currency_id' => Currency::factory(),
            'method_code' => 'manual',
            'payment_date' => now(),
            'received_by' => User::factory(),
            'status' => 'captured',
        ];
    }
}
