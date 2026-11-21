<?php

namespace Database\Factories;

use App\Models\BibliographicRecord;
use App\Models\LibraryBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibliographicRecord>
 */
class BibliographicRecordFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'record_no' => fake()->unique()->bothify('BIB-####'),
            'title' => fake()->bothify('كتاب ???'),
            'author' => fake()->name(),
            'classification_code' => fake()->bothify('???-###'),
            'language_code' => 'ar',
            'publisher' => fake()->company(),
            'published_year' => fake()->year(),
            'library_branch_id' => LibraryBranch::factory(),
            'status' => 'active',
        ];
    }
}
