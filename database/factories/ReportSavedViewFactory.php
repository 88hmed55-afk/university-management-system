<?php

namespace Database\Factories;

use App\Models\ReportDefinition;
use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportSavedView>
 */
class ReportSavedViewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->bothify('عرض ???'),
            'report_definition_id' => ReportDefinition::factory(),
            'actor_id' => User::factory(),
            'filters' => [],
            'scope_snapshot' => ['scope_code' => 'institution'],
            'status' => 'active',
        ];
    }
}
