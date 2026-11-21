<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'announcement_no' => fake()->unique()->bothify('ANN-####'),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'locale' => 'ar',
            'audience_scope_code' => 'institution',
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }
}
