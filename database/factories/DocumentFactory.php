<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => Campus::class,
            'owner_id' => Campus::factory(),
            'document_type' => 'GENERIC',
            'storage_key' => 'private/documents/'.fake()->uuid(),
            'original_name' => fake()->bothify('document-???.pdf'),
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1, 100000),
            'checksum' => hash('sha256', fake()->uuid()),
            'uploaded_by' => User::factory(),
        ];
    }
}
