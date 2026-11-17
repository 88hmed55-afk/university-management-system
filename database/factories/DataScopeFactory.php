<?php

namespace Database\Factories;

use App\DataScopeCode;
use App\Models\DataScope;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataScope>
 */
class DataScopeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(DataScopeCode::cases()),
        ];
    }

    public function forCode(DataScopeCode $code): static
    {
        return $this->state(fn (array $attributes): array => ['code' => $code]);
    }
}
