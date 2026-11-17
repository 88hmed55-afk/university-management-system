<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\FeePolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeePolicy>
 */
class FeePolicyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('FP-####'),
            'version_no' => 1,
            'name' => fake()->bothify('سياسة ???'),
            'currency_id' => Currency::factory(),
            'effective_from' => '2040-01-01',
            'status' => 'published',
            'created_by' => User::factory(),
            'decision_reference' => fake()->unique()->bothify('DEC-####'),
        ];
    }
}
