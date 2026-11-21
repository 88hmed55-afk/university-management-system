<?php

namespace App\Actions;

use App\Models\ReportDefinition;
use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\ReportAccess;
use App\Support\ReportAudit;
use App\Support\ReportQuery;
use App\Support\ReportSources;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SaveReportView
{
    /** A saved view never carries authority: the current actor's scope is stored as a snapshot only. */
    public function __invoke(
        User $actor,
        ReportDefinition $definition,
        string $name,
        array $filters = [],
        ?string $correlationId = null,
    ): ReportSavedView {
        if (! ReportAccess::canRun($definition, $actor)) {
            throw new AuthorizationException('ليس لديك صلاحية حفظ عرض لهذا التقرير.');
        }

        if (trim($name) === '') {
            throw new InvalidArgumentException('اسم العرض المحفوظ مطلوب.');
        }

        $cleanFilters = ReportSources::validateFilters($definition->source_code, $filters);

        if (ReportSavedView::query()
            ->where('actor_id', $actor->getKey())
            ->where('report_definition_id', $definition->getKey())
            ->where('name', $name)
            ->exists()) {
            throw new InvalidArgumentException('عرض بهذا الاسم موجود مسبقاً للمستخدم.');
        }

        $view = null;

        DB::transaction(function () use ($actor, $definition, $name, $cleanFilters, $correlationId, &$view): void {
            $view = ReportSavedView::create([
                'name' => $name,
                'report_definition_id' => $definition->getKey(),
                'actor_id' => $actor->getKey(),
                'filters' => $cleanFilters,
                'scope_snapshot' => ReportQuery::scopeSnapshot($definition, $actor),
                'status' => 'active',
            ]);

            ReportAudit::record(
                $actor, 'reports.view_saved', ReportSavedView::class,
                (string) $view->getKey(),
                ['report_code' => $definition->code, 'filter_keys' => array_keys($cleanFilters)],
                null, $correlationId,
            );
        });

        return $view->refresh();
    }
}
