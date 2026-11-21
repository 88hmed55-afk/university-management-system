<?php

namespace Database\Factories;

use App\Models\Refund;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'refund_no' => fake()->unique()->bothify('RFD-####'),
            'original_payment_id' => StudentPayment::factory(),
            'amount' => '10.00',
            'reason' => 'سبب اختبار',
            'requested_by' => User::factory(),
            'status' => 'requested',
        ];
    }
}
