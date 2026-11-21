<?php

namespace Database\Factories;

use App\Models\ReportDefinition;
use App\Models\ReportRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportRun>
 */
class ReportRunFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'run_no' => fake()->unique()->bothify('RUN-####'),
            'report_definition_id' => ReportDefinition::factory(),
            'actor_id' => User::factory(),
            'correlation_id' => fake()->uuid(),
            'filters' => [],
            'scope_snapshot' => ['scope_code' => 'institution'],
            'row_count' => 0,
            'checksum' => hash('sha256', fake()->uuid()),
            'status' => 'completed',
            'generated_at' => now(),
        ];
    }
}
