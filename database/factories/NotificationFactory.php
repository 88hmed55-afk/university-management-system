<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'notification_no' => fake()->unique()->bothify('NTF-####'),
            'recipient_id' => User::factory(),
            'channel_code' => 'internal',
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => 'unread',
        ];
    }
}
