<?php

namespace Database\Factories;

use App\Models\RequestEvent;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestEvent>
 */
class RequestEventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'event_type' => 'submitted',
            'actor_id' => User::factory(),
        ];
    }
}
