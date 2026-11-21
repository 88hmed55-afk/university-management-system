<?php

namespace App\Actions;

use App\Models\ReportDashboard;
use App\Models\ReportDefinition;
use App\Models\User;
use App\RoleCode;
use App\Support\ReportAccess;
use App\Support\ReportAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DefineDashboard
{
    /**
     * A dashboard only groups approved definitions that share one scope and
     * targets one role from the approved role catalogue (docs/10).
     *
     * @param  array<int, int>  $definitionIds
     */
    public function __invoke(
        User $actor,
        string $code,
        string $name,
        string $roleCode,
        string $scopeCode,
        array $definitionIds,
        string $decisionReference,
        ?string $correlationId = null,
    ): ReportDashboard {
        if (! ReportAccess::canConfigure($actor)) {
            throw new AuthorizationException('تعريف لوحات التقارير مقصور على جهة الإدارة المخولّة.');
        }

        if (trim($code) === '' || trim($name) === '' || trim($decisionReference) === '') {
            throw new InvalidArgumentException('بيانات تعريف اللوحة غير مكتملة.');
        }

        if (RoleCode::tryFrom($roleCode) === null) {
            throw new InvalidArgumentException('دور اللوحة غير معروف.');
        }

        if (! in_array($scopeCode, ReportAccess::supportedScopeCodes(), true)) {
            throw new InvalidArgumentException('نطاق اللوحة غير مدعوم.');
        }

        if ($definitionIds === [] || array_values($definitionIds) !== array_values(array_unique($definitionIds))) {
            throw new InvalidArgumentException('اللوحة تحتاج تعريفات تقرير فريدة.');
        }

        $definitions = ReportDefinition::query()->whereKey($definitionIds)->get();

        if ($definitions->count() !== count($definitionIds)) {
            throw new InvalidArgumentException('أحد تعريفات التقرير غير موجود.');
        }

        foreach ($definitions as $definition) {
            if ($definition->status !== 'approved' || $definition->scope_code !== $scopeCode) {
                throw new InvalidArgumentException('لوحة التقارير لا تجمع إلا تعريفات معتمدة بنفس النطاق.');
            }
        }

        if (ReportDashboard::query()->where('code', $code)->exists()) {
            throw new InvalidArgumentException('رمز اللوحة مستخدم مسبقاً.');
        }

        $dashboard = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $code, $name, $roleCode, $scopeCode, $definitionIds,
            $decisionReference, $correlationId, &$dashboard,
        ): void {
            $dashboard = ReportDashboard::create([
                'code' => $code,
                'name' => $name,
                'role_code' => $roleCode,
                'scope_code' => $scopeCode,
                'definition_ids' => array_values($definitionIds),
                'decision_reference' => $decisionReference,
                'status' => 'active',
                'created_by' => $actor->getKey(),
            ]);

            ReportAudit::record(
                $actor, 'reports.dashboard_defined', ReportDashboard::class,
                (string) $dashboard->getKey(),
                ['code' => $code, 'role_code' => $roleCode, 'definition_count' => count($definitionIds)],
                null, $correlationId,
            );
        });

        return $dashboard->refresh();
    }
}
