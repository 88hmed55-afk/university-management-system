<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\CirculationTransaction;
use App\Models\ReportDashboard;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\ReportSavedView;
use App\Models\ServiceRequest;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use Illuminate\Support\Facades\DB;

/**
 * Read-only integrity snapshot used by migration readiness checks. Every check
 * is expressed with scoped Eloquent/query-builder calls; nothing is repaired
 * or deleted automatically.
 */
final class DataIntegrityAudit
{
    /**
     * @return array<int, array{label: string, count: int}>
     */
    public static function orphanChecks(): array
    {
        return [
            ['label' => 'student_invoices.student_id', 'count' => StudentInvoice::query()->whereDoesntHave('student')->count()],
            ['label' => 'student_payments.student_id', 'count' => StudentPayment::query()->whereDoesntHave('student')->count()],
            ['label' => 'circulation_transactions.item_copy_id', 'count' => CirculationTransaction::query()->whereDoesntHave('copy')->count()],
            ['label' => 'service_requests.service_request_type_id', 'count' => ServiceRequest::query()->whereDoesntHave('type')->count()],
            ['label' => 'report_runs.report_definition_id', 'count' => ReportRun::query()->whereDoesntHave('definition')->count()],
            ['label' => 'report_exports.report_definition_id', 'count' => ReportExport::query()->whereDoesntHave('definition')->count()],
            ['label' => 'audit_logs.actor_id', 'count' => AuditLog::query()->whereNotNull('actor_id')->whereDoesntHave('actor')->count()],
        ];
    }

    /**
     * @return array<int, array{table: string, column: string, duplicates: int}>
     */
    public static function duplicateChecks(): array
    {
        $candidates = [
            ['users', 'email'],
            ['campuses', 'code'],
            ['roles', 'code'],
            ['report_definitions', 'code'],
            ['report_exports', 'export_no'],
        ];

        $result = [];

        foreach ($candidates as [$table, $column]) {
            $duplicates = DB::table($table)
                ->select($column)
                ->groupBy($column)
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();

            $result[] = ['table' => $table, 'column' => $column, 'duplicates' => $duplicates];
        }

        return $result;
    }

    /**
     * @return array<int, class-string>
     */
    public static function immutableModels(): array
    {
        return [
            AuditLog::class,
            ReportDefinition::class,
            ReportDashboard::class,
            ReportRun::class,
            ReportSavedView::class,
            ReportExport::class,
        ];
    }
}
