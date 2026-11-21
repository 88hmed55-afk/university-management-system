<?php

namespace App\Actions;

use App\Models\ReportDefinition;
use App\Models\ReportRun;
use App\Models\User;
use App\Support\ReportAccess;
use App\Support\ReportAudit;
use App\Support\ReportQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RunReport
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{run: ReportRun, rows: array<int, array<string, mixed>>, columns: array<int, string>, scope: array<string, mixed>, filters: array<string, mixed>, checksum: string}
     */
    public function __invoke(
        User $actor,
        ReportDefinition $definition,
        string $runNo,
        array $filters = [],
        ?string $correlationId = null,
    ): array {
        if (! ReportAccess::canRun($definition, $actor)) {
            throw new AuthorizationException('ليس لديك صلاحية تشغيل هذا التقرير أو نطاق بياناته.');
        }

        if (trim($runNo) === '') {
            throw new \InvalidArgumentException('رقم تشغيل التقرير مطلوب.');
        }

        if (ReportRun::query()->where('run_no', $runNo)->exists()) {
            throw new \InvalidArgumentException('رقم تشغيل التقرير مستخدم مسبقاً.');
        }

        $result = ReportQuery::execute($definition, $actor, $filters);
        $correlationId ??= Str::uuid()->toString();
        $run = null;

        DB::transaction(function () use (
            $actor, $definition, $runNo, $result, $correlationId, &$run,
        ): void {
            $run = ReportRun::create([
                'run_no' => $runNo,
                'report_definition_id' => $definition->getKey(),
                'actor_id' => $actor->getKey(),
                'correlation_id' => $correlationId,
                'filters' => $result['filters'],
                'scope_snapshot' => $result['scope'],
                'row_count' => count($result['rows']),
                'checksum' => $result['checksum'],
                'status' => 'completed',
                'generated_at' => now(),
            ]);

            ReportAudit::record(
                $actor, 'reports.run', ReportDefinition::class,
                (string) $definition->getKey(),
                [
                    'run_no' => $runNo,
                    'filter_keys' => array_keys($result['filters']),
                    'scope_code' => $result['scope']['scope_code'],
                    'row_count' => count($result['rows']),
                    'checksum' => $result['checksum'],
                ],
                null, $correlationId,
            );
        });

        return [
            'run' => $run->refresh(),
            'rows' => $result['rows'],
            'columns' => $result['columns'],
            'scope' => $result['scope'],
            'filters' => $result['filters'],
            'checksum' => $result['checksum'],
        ];
    }
}
