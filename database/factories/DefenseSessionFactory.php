<?php

namespace Database\Factories;

use App\Models\DefenseSession;
use App\Models\PostgraduateRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DefenseSession>
 */
class DefenseSessionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'session_no' => fake()->unique()->bothify('DEF-####'),
            'postgraduate_record_id' => PostgraduateRecord::factory(),
            'scheduled_at' => now()->addDays(7),
            'location' => fake()->bothify('قاعة ???'),
            'requirements_reference' => fake()->bothify('REQ-REF-####'),
            'acted_by' => User::factory(),
            'status' => 'scheduled',
        ];
    }
}
