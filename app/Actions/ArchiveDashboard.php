<?php

namespace App\Actions;

use App\Models\ReportDashboard;
use App\Models\User;
use App\Support\ReportAccess;
use App\Support\ReportAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ArchiveDashboard
{
    public function __invoke(
        ReportDashboard $dashboard,
        User $actor,
        string $reason,
        string $decisionReference,
        ?string $correlationId = null,
    ): ReportDashboard {
        if (! ReportAccess::canConfigure($actor)) {
            throw new AuthorizationException('أرشفة لوحات التقارير مقصورة على جهة الإدارة المخولّة.');
        }

        if (trim($reason) === '' || trim($decisionReference) === '') {
            throw new InvalidArgumentException('سبب الأرشفة ومرجع القرار مطلوبان.');
        }

        if ($dashboard->status === 'archived') {
            throw new InvalidArgumentException('اللوحة مؤرشفة مسبقاً.');
        }

        DB::transaction(function () use ($dashboard, $actor, $reason, $correlationId): void {
            $dashboard->status = 'archived';
            $dashboard->archived_at = now();
            $dashboard->archive_reason = $reason;
            $dashboard->save();

            ReportAudit::record(
                $actor, 'reports.dashboard_archived', ReportDashboard::class,
                (string) $dashboard->getKey(),
                ['code' => $dashboard->code, 'status' => 'archived'],
                $reason, $correlationId,
            );
        });

        return $dashboard->refresh();
    }
}
