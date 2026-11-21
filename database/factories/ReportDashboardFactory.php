<?php

namespace Database\Factories;

use App\Models\ReportDashboard;
use App\Models\User;
use App\RoleCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportDashboard>
 */
class ReportDashboardFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('DASH-####'),
            'name' => fake()->bothify('لوحة ???'),
            'role_code' => RoleCode::ReportReader->value,
            'scope_code' => 'institution',
            'definition_ids' => [],
            'decision_reference' => 'DASH-REF-'.fake()->unique()->numerify('###'),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
