<?php

namespace Database\Factories;

use App\Models\CirculationTransaction;
use App\Models\ItemCopy;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CirculationTransaction>
 */
class CirculationTransactionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'transaction_no' => fake()->unique()->bothify('CIR-####'),
            'item_copy_id' => ItemCopy::factory(),
            'beneficiary_type' => Student::class,
            'beneficiary_id' => Student::factory(),
            'transaction_type' => 'checkout',
            'occurred_at' => now(),
            'acted_by' => User::factory(),
            'status' => 'recorded',
        ];
    }
}
