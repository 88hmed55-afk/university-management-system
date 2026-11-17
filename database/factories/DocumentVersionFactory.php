<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'version_no' => 1,
            'storage_key' => 'private/documents/versions/'.fake()->uuid(),
            'original_name' => fake()->bothify('version-???.pdf'),
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1, 100000),
            'checksum' => hash('sha256', fake()->uuid()),
            'created_by' => User::factory(),
        ];
    }
}
