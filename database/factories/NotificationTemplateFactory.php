<?php

namespace Database\Factories;

use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('TMPL-####'),
            'version_no' => 1,
            'channel_code' => 'internal',
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }
}
