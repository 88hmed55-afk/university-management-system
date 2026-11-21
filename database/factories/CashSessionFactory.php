<?php

namespace Database\Factories;

use App\Models\Cashbox;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashSession>
 */
class CashSessionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'cashbox_id' => Cashbox::factory(),
            'opened_by' => User::factory(),
            'opened_at' => now(),
            'status' => 'open',
        ];
    }
}
