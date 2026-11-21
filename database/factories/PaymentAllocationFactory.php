<?php

namespace Database\Factories;

use App\Models\PaymentAllocation;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAllocation>
 */
class PaymentAllocationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_payment_id' => StudentPayment::factory(),
            'student_invoice_id' => StudentInvoice::factory(),
            'amount' => '50.00',
            'created_by' => User::factory(),
        ];
    }
}
