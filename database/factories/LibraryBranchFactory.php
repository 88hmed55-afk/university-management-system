<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\LibraryBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryBranch>
 */
class LibraryBranchFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('LIB-####'),
            'name' => fake()->bothify('مكتبة ???'),
            'campus_id' => Campus::factory(),
            'status' => 'active',
        ];
    }
}
