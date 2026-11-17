<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\PersonIdentifier;
use App\Models\PersonIdentifierType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonIdentifier>
 */
class PersonIdentifierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'identifier_type_id' => PersonIdentifierType::factory(),
            'value' => fake()->numerify('##########'),
            'effective_from' => '2026-01-01',
        ];
    }
}
