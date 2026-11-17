<?php

namespace Database\Factories;

use App\Models\Role;
use App\RoleCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(RoleCode::cases()),
            'is_system' => true,
        ];
    }

    public function forCode(RoleCode $code): static
    {
        return $this->state(fn (array $attributes): array => ['code' => $code]);
    }
}
