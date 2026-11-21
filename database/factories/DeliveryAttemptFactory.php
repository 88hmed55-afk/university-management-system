<?php

namespace Database\Factories;

use App\Models\DeliveryAttempt;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryAttempt>
 */
class DeliveryAttemptFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'notification_id' => Notification::factory(),
            'attempt_no' => 1,
            'channel_code' => 'internal',
            'status' => 'success',
        ];
    }
}
