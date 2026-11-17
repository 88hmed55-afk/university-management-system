<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action' => 'role.assigned',
            'subject_type' => Role::class,
            'subject_id' => '1',
            'outcome' => 'success',
            'reason' => 'بيان اختبار',
        ];
    }
}
