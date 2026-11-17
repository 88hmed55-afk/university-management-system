<?php

namespace Database\Factories;

use App\Models\FeeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeItem>
 */
class FeeItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('FI-####'),
            'name' => fake()->bothify('بند ???'),
            'status' => 'active',
        ];
    }
}
