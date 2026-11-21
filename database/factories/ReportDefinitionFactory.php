<?php

namespace Database\Factories;

use App\Models\ReportDefinition;
use App\Models\User;
use App\RoleCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportDefinition>
 */
class ReportDefinitionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('RPT-####'),
            'name' => fake()->bothify('تقرير ???'),
            'owner_id' => User::factory(),
            'source_code' => 'campuses',
            'metric_definition' => 'عدد الأحرم المسجلة حسب الحالة؛ التعريف موثّق بالمرجع.',
            'metric_reference' => 'METRIC-REF-'.fake()->unique()->numerify('###'),
            'scope_code' => 'institution',
            'allowed_role_codes' => [RoleCode::UniversityPresident->value, RoleCode::ReportReader->value],
            'allowed_export_role_codes' => [RoleCode::UniversityPresident->value, RoleCode::InternalAuditor->value],
            'filter_definition' => ['code', 'status'],
            'max_rows' => 100,
            'max_rows_reference' => 'CAP-REF-'.fake()->unique()->numerify('###'),
            'freshness_code' => 'live',
            'include_drafts' => false,
            'status' => 'draft',
            'created_by' => User::factory(),
            'decision_reference' => 'DEC-REF-'.fake()->unique()->numerify('###'),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => 'approved']);
    }
}
