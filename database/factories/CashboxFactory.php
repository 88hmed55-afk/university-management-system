<?php

namespace Database\Factories;

use App\Models\Cashbox;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cashbox>
 */
class CashboxFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CB-####'),
            'name' => fake()->bothify('صندوق ???'),
            'currency_id' => Currency::factory(),
            'status' => 'active',
        ];
    }
}
