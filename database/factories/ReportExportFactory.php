<?php

namespace Database\Factories;

use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportExport>
 */
class ReportExportFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'export_no' => fake()->unique()->bothify('EXP-####'),
            'report_definition_id' => ReportDefinition::factory(),
            'actor_id' => User::factory(),
            'format_code' => 'csv',
            'storage_key' => 'reports/'.fake()->uuid().'.csv',
            'checksum' => hash('sha256', fake()->uuid()),
            'row_count' => 0,
            'max_rows' => 100,
            'limit_reference' => 'CAP-REF-'.fake()->unique()->numerify('###'),
            'filters' => [],
            'scope_snapshot' => ['scope_code' => 'institution'],
            'include_pii' => false,
            'idempotency_key' => null,
            'status' => 'completed',
            'correlation_id' => fake()->uuid(),
            'completed_at' => now(),
        ];
    }
}
