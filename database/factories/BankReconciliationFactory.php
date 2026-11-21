<?php

namespace Database\Factories;

use App\Models\BankReconciliation;
use App\Models\Cashbox;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankReconciliation>
 */
class BankReconciliationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'cashbox_id' => Cashbox::factory(),
            'period_start' => '2040-01-01',
            'period_end' => '2040-01-31',
            'expected_amount' => '100.00',
            'actual_amount' => '100.00',
            'difference_amount' => '0.00',
            'status' => 'recorded',
            'prepared_by' => User::factory(),
        ];
    }
}
