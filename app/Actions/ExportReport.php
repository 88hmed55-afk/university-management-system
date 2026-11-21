<?php

namespace App\Actions;

use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\User;
use App\Support\ReportAccess;
use App\Support\ReportAudit;
use App\Support\ReportQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class ExportReport
{
    /**
     * CSV is the only driver available; official PDF/XLSX templates require an
     * approved decision (P-022). Every attempt, including refusals, is audited.
     *
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(
        User $actor,
        ReportDefinition $definition,
        string $exportNo,
        string $formatCode,
        int $maxRows,
        string $limitReference,
        array $filters = [],
        bool $includePii = false,
        ?string $piiBasisReference = null,
        ?string $reason = null,
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
    ): ReportExport {
        $correlationId ??= Str::uuid()->toString();

        if (! ReportAccess::canExport($definition, $actor)) {
            $this->refuse($actor, $definition, 'لا صلاحية تصدير لهذا التقرير أو نطاق بياناته.', $correlationId, $reason);
        }

        if ($formatCode !== 'csv') {
            $this->refuse($actor, $definition, 'قوالب PDF/XLSX الرسمية غير معتمدة بعد (P-022).', $correlationId, $reason);
        }

        if ($maxRows < 1 || $maxRows > (int) $definition->max_rows) {
            $this->refuse($actor, $definition, 'حد صفوف التصدير خارج الحد المعتمد للتقرير.', $correlationId, $reason);
        }

        if (trim($limitReference) === '') {
            $this->refuse($actor, $definition, 'مرجع قرار حد التصدير مطلوب.', $correlationId, $reason);
        }

        if ($includePii && trim((string) $piiBasisReference) === '') {
            $this->refuse($actor, $definition, 'إظهار بيانات التعريف يتطلب مرجع أساس نظامي.', $correlationId, $reason);
        }

        if (trim($exportNo) === '') {
            throw new InvalidArgumentException('رقم التصدير مطلوب.');
        }

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $existing = ReportExport::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                if ((int) $existing->actor_id !== (int) $actor->getKey()
                    || (int) $existing->report_definition_id !== (int) $definition->getKey()) {
                    throw new AuthorizationException('مفتاح التكرار يعود لتصدير آخر.');
                }

                return $existing->refresh();
            }
        }

        if (ReportExport::query()->where('export_no', $exportNo)->exists()) {
            throw new InvalidArgumentException('رقم التصدير مستخدم مسبقاً.');
        }

        try {
            $result = ReportQuery::execute($definition, $actor, $filters, $includePii);
            $csv = $this->toCsv($result['columns'], $result['rows']);
            $storageKey = 'reports/'.Str::uuid()->toString().'.csv';

            if (! Storage::disk('local')->put($storageKey, $csv)) {
                throw new RuntimeException('تعذّر حفظ ملف التصدير في التخزين الخاص.');
            }

            $export = null;

            DB::transaction(function () use (
                $actor, $definition, $exportNo, $formatCode, $maxRows, $limitReference,
                $result, $includePii, $piiBasisReference, $reason, $idempotencyKey,
                $correlationId, $storageKey, &$export,
            ): void {
                $export = ReportExport::create([
                    'export_no' => $exportNo,
                    'report_definition_id' => $definition->getKey(),
                    'actor_id' => $actor->getKey(),
                    'format_code' => $formatCode,
                    'storage_key' => $storageKey,
                    'checksum' => hash('sha256', Storage::disk('local')->get($storageKey) ?? ''),
                    'row_count' => count($result['rows']),
                    'max_rows' => $maxRows,
                    'limit_reference' => $limitReference,
                    'filters' => $result['filters'],
                    'scope_snapshot' => $result['scope'],
                    'include_pii' => $includePii,
                    'pii_basis_reference' => $piiBasisReference,
                    'idempotency_key' => $idempotencyKey,
                    'status' => 'completed',
                    'correlation_id' => $correlationId,
                    'completed_at' => now(),
                ]);

                ReportAudit::record(
                    $actor, 'reports.export', ReportDefinition::class,
                    (string) $definition->getKey(),
                    [
                        'export_no' => $exportNo,
                        'format_code' => $formatCode,
                        'filter_keys' => array_keys($result['filters']),
                        'scope_code' => $result['scope']['scope_code'],
                        'row_count' => count($result['rows']),
                        'checksum' => $export->checksum,
                        'include_pii' => $includePii,
                        'limit_reference' => $limitReference,
                        'exported_at' => $export->completed_at?->toISOString(),
                    ],
                    $reason, $correlationId,
                );
            });

            return $export->refresh();
        } catch (Throwable $exception) {
            ReportAudit::record(
                $actor, 'reports.export_failed', ReportDefinition::class,
                (string) $definition->getKey(),
                ['export_no' => $exportNo, 'format_code' => $formatCode],
                $exception->getMessage(), $correlationId, 'failed',
            );

            throw $exception;
        }
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function toCsv(array $columns, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('تعذّر إنشاء ملف التصدير.');
        }

        fputcsv($handle, $columns);

        foreach ($rows as $row) {
            $line = [];

            foreach ($columns as $column) {
                $value = $row[$column] ?? '';
                $line[] = $value === null ? '' : (string) $value;
            }

            fputcsv($handle, $line);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    private function refuse(
        User $actor,
        ReportDefinition $definition,
        string $reason,
        string $correlationId,
        ?string $requestedReason = null,
    ): never {
        ReportAudit::record(
            $actor, 'reports.export_refused', ReportDefinition::class,
            (string) $definition->getKey(),
            ['report_code' => $definition->code, 'status' => 'refused'],
            $requestedReason ?? $reason, $correlationId, 'refused',
        );

        throw new AuthorizationException($reason);
    }
}
