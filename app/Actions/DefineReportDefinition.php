<?php

namespace App\Actions;

use App\Models\ReportDefinition;
use App\Models\User;
use App\RoleCode;
use App\Support\ReportAccess;
use App\Support\ReportAudit;
use App\Support\ReportSources;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DefineReportDefinition
{
    /**
     * A definition stores the metric definition and every policy reference; the
     * system never invents an indicator, cap or freshness schedule.
     *
     * @param  array<int, string>  $allowedRoleCodes
     * @param  array<int, string>  $allowedExportRoleCodes
     * @param  array<int, string>  $filterDefinition
     */
    public function __invoke(
        User $actor,
        string $code,
        string $name,
        User $owner,
        string $sourceCode,
        string $metricDefinition,
        string $metricReference,
        string $scopeCode,
        array $allowedRoleCodes,
        array $allowedExportRoleCodes,
        array $filterDefinition,
        int $maxRows,
        string $maxRowsReference,
        string $decisionReference,
        string $freshnessCode = 'live',
        ?string $freshnessReference = null,
        bool $includeDrafts = false,
        ?string $includeDraftsReference = null,
        ?string $correlationId = null,
    ): ReportDefinition {
        if (! ReportAccess::canConfigure($actor)) {
            throw new AuthorizationException('تعريف التقارير مقصور على جهة الإدارة المخولّة.');
        }

        foreach (['code', 'name', 'metricDefinition', 'metricReference', 'maxRowsReference', 'decisionReference'] as $field) {
            if (trim($$field) === '') {
                throw new InvalidArgumentException('بيانات تعريف التقرير غير مكتملة.');
            }
        }

        $descriptor = ReportSources::descriptor($sourceCode);

        if (! in_array($scopeCode, ReportAccess::supportedScopeCodes(), true) || $scopeCode !== $descriptor['scope']) {
            throw new InvalidArgumentException('نطاق التقرير لا يطابق مصدر بياناته.');
        }

        foreach ([$allowedRoleCodes, $allowedExportRoleCodes] as $roles) {
            if ($roles === [] || array_values($roles) !== array_values(array_unique($roles))) {
                throw new InvalidArgumentException('أدوار التقرير والتصدير يجب أن تكون غير فارغة وفريدة.');
            }

            foreach ($roles as $role) {
                if (RoleCode::tryFrom($role) === null) {
                    throw new InvalidArgumentException('رمز دور غير معروف في تعريف التقرير.');
                }
            }
        }

        foreach ($filterDefinition as $filter) {
            if (! is_string($filter) || ! in_array($filter, $descriptor['filters'], true)) {
                throw new InvalidArgumentException('فلتر غير مسموح لمصدر التقرير هذا.');
            }
        }

        if ($maxRows < 1) {
            throw new InvalidArgumentException('حد الصفوف يجب أن يكون موجباً.');
        }

        if (! in_array($freshnessCode, ['live', 'periodic'], true)
            || ($freshnessCode === 'periodic' && trim((string) $freshnessReference) === '')) {
            throw new InvalidArgumentException('نوع تحديث التقرير أو مرجع جدولته غير صالح.');
        }

        if ($includeDrafts && trim((string) $includeDraftsReference) === '') {
            throw new InvalidArgumentException('إدخال المسودات في تقرير معتمد يتطلب مرجع قرار.');
        }

        if (ReportDefinition::query()->where('code', $code)->exists()) {
            throw new InvalidArgumentException('رمز تعريف التقرير مستخدم مسبقاً.');
        }

        $definition = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $code, $name, $owner, $sourceCode, $metricDefinition, $metricReference,
            $scopeCode, $allowedRoleCodes, $allowedExportRoleCodes, $filterDefinition,
            $maxRows, $maxRowsReference, $decisionReference, $freshnessCode,
            $freshnessReference, $includeDrafts, $includeDraftsReference,
            $correlationId, &$definition,
        ): void {
            $definition = ReportDefinition::create([
                'code' => $code,
                'name' => $name,
                'owner_id' => $owner->getKey(),
                'source_code' => $sourceCode,
                'metric_definition' => $metricDefinition,
                'metric_reference' => $metricReference,
                'scope_code' => $scopeCode,
                'allowed_role_codes' => array_values($allowedRoleCodes),
                'allowed_export_role_codes' => array_values($allowedExportRoleCodes),
                'filter_definition' => array_values($filterDefinition),
                'max_rows' => $maxRows,
                'max_rows_reference' => $maxRowsReference,
                'freshness_code' => $freshnessCode,
                'freshness_reference' => $freshnessReference,
                'include_drafts' => $includeDrafts,
                'include_drafts_reference' => $includeDraftsReference,
                'status' => 'draft',
                'created_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            ReportAudit::record(
                $actor, 'reports.definition_defined', ReportDefinition::class,
                (string) $definition->getKey(),
                ['code' => $code, 'source_code' => $sourceCode, 'scope_code' => $scopeCode],
                null, $correlationId,
            );
        });

        return $definition->refresh();
    }
}
