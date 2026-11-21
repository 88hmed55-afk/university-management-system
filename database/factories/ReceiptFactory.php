<?php

namespace Database\Factories;

use App\Models\Receipt;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'receipt_no' => fake()->unique()->bothify('RCP-####'),
            'student_payment_id' => StudentPayment::factory(),
            'payload' => ['amount' => '100.00'],
            'issued_at' => now(),
            'issued_by' => User::factory(),
        ];
    }
}
