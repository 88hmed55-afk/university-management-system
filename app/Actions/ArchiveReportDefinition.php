<?php

namespace App\Actions;

use App\Models\ReportDefinition;
use App\Models\User;
use App\Support\ReportAccess;
use App\Support\ReportAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ArchiveReportDefinition
{
    public function __invoke(
        ReportDefinition $definition,
        User $actor,
        string $reason,
        string $decisionReference,
        ?string $correlationId = null,
    ): ReportDefinition {
        if (! ReportAccess::canConfigure($actor)) {
            throw new AuthorizationException('أرشفة تعريفات التقارير مقصورة على جهة الإدارة المخولّة.');
        }

        if (trim($reason) === '' || trim($decisionReference) === '') {
            throw new InvalidArgumentException('سبب الأرشفة ومرجع القرار مطلوبان.');
        }

        if ($definition->status === 'archived') {
            throw new InvalidArgumentException('التعريف مؤرشف مسبقاً.');
        }

        DB::transaction(function () use ($definition, $actor, $reason, $decisionReference, $correlationId): void {
            $definition->status = 'archived';
            $definition->archived_at = now();
            $definition->archive_reason = $reason;
            $definition->decision_reference = $decisionReference;
            $definition->save();

            ReportAudit::record(
                $actor, 'reports.definition_archived', ReportDefinition::class,
                (string) $definition->getKey(),
                ['code' => $definition->code, 'status' => 'archived'],
                $reason, $correlationId,
            );
        });

        return $definition->refresh();
    }
}
