<?php

namespace App\Actions;

use App\Models\ReportDefinition;
use App\Models\User;
use App\Support\ReportAccess;
use App\Support\ReportAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ApproveReportDefinition
{
    public function __invoke(
        ReportDefinition $definition,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): ReportDefinition {
        if (! ReportAccess::canConfigure($actor)) {
            throw new AuthorizationException('اعتماد تعريفات التقارير مقصور على جهة الإدارة المخولّة.');
        }

        if ((int) $definition->created_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لمن أنشأ تعريف التقرير أن يعتمده.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع اعتماد التقرير مطلوب.');
        }

        if ($definition->status !== 'draft') {
            throw new InvalidArgumentException('التعريف ليس في حالة انتظار الاعتماد.');
        }

        DB::transaction(function () use ($definition, $actor, $decisionReference, $correlationId): void {
            $definition->status = 'approved';
            $definition->approved_by = $actor->getKey();
            $definition->approved_at = now();
            $definition->decision_reference = $decisionReference;
            $definition->save();

            ReportAudit::record(
                $actor, 'reports.definition_approved', ReportDefinition::class,
                (string) $definition->getKey(),
                ['code' => $definition->code, 'status' => 'approved'],
                null, $correlationId,
            );
        });

        return $definition->refresh();
    }
}
