<?php

namespace Database\Factories;

use App\Models\BibliographicRecord;
use App\Models\ItemCopy;
use App\Models\LibraryBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemCopy>
 */
class ItemCopyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'copy_no' => fake()->unique()->bothify('CPY-####'),
            'bibliographic_record_id' => BibliographicRecord::factory(),
            'library_branch_id' => LibraryBranch::factory(),
            'location_code' => fake()->bothify('LOC-???'),
            'status' => 'available',
            'acquired_at' => fake()->date(),
        ];
    }
}
