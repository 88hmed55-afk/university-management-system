<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A read-only launch gate. It reports technical facts and the still-open
 * university decisions; it never issues a GO decision on behalf of the university.
 */
final class LaunchReadinessGate
{
    /**
     * @return array{status: string, can_launch: bool, checks: array<int, array{key: string, ok: bool, detail: string}>, open_decisions: array<int, array{id: string, owner: string, source: string}>, summary: string}
     */
    public function evaluate(): array
    {
        $checks = $this->technicalChecks();
        $failures = array_values(array_filter($checks, fn (array $check): bool => ! $check['ok']));
        $openDecisions = $this->openDecisions();
        $canLaunch = false;

        $status = $failures !== [] ? 'NO_GO' : 'PENDING_HUMAN_APPROVAL';

        $summary = $failures !== []
            ? 'فحوص تقنية غير مكتملة؛ الإطلاق ممنوع.'
            : 'الفحوص التقنية مكتملة؛ قرار الإطلاق رهناً باعتماد السياسات المعلّقة واختبارات القبول.';

        return [
            'status' => $status,
            'can_launch' => $canLaunch,
            'checks' => $checks,
            'open_decisions' => $openDecisions,
            'summary' => $summary,
        ];
    }

    /** @return array<int, array{key: string, ok: bool, detail: string}> */
    private function technicalChecks(): array
    {
        $migrationFiles = glob(database_path('migrations/*.php')) ?: [];
        $applied = Schema::hasTable('migrations') ? DB::table('migrations')->count() : 0;
        $runbook = base_path('Documentation/Operations/LAUNCH_RUNBOOK.md');
        $rpoRequest = base_path('Documentation/QA/RPO_RTO_DECISION_REQUEST.md');
        $ci = base_path('.github/workflows/tests.yml');

        return [
            [
                'key' => 'migrations',
                'ok' => $applied >= count($migrationFiles),
                'detail' => "{$applied} من ".count($migrationFiles).' migration مطبّقة',
            ],
            [
                'key' => 'app_key',
                'ok' => filled(config('app.key')),
                'detail' => filled(config('app.key')) ? 'مفتاح التطبيق مضبوط' : 'مفتاح التطبيق مفقود',
            ],
            [
                'key' => 'private_storage',
                'ok' => is_writable(storage_path('app/private')),
                'detail' => is_writable(storage_path('app/private'))
                    ? 'التخزين الخاص قابل للكتابة'
                    : 'التخزين الخاص غير قابل للكتابة',
            ],
            [
                'key' => 'ci',
                'ok' => is_file($ci),
                'detail' => is_file($ci) ? 'بوابة CI موجودة' : 'بوابة CI مفقودة',
            ],
            [
                'key' => 'operations_docs',
                'ok' => is_file($runbook) && is_file($rpoRequest),
                'detail' => is_file($runbook) && is_file($rpoRequest)
                    ? 'دليل الإطلاق وطلب RPO/RTO موجودان'
                    : 'وثائق التشغيل ناقصة',
            ],
        ];
    }

    /**
     * Mirrors the authoritative register. The register itself remains the source
     * of truth for owners, references and closure evidence.
     *
     * @return array<int, array{id: string, owner: string, source: string}>
     */
    private function openDecisions(): array
    {
        $owners = [
            'P-001' => 'الإدارة',
            'P-002' => 'مجلس الجامعة',
            'P-003' => 'الشؤون الأكاديمية',
            'P-004' => 'الشؤون الأكاديمية',
            'P-005' => 'القبول/المجلس',
            'P-006' => 'التسجيل/HR',
            'P-007' => 'الأكاديمية',
            'P-008' => 'الأكاديمية',
            'P-009' => 'الأكاديمية',
            'P-010' => 'الأكاديمية',
            'P-011' => 'المجالس الأكاديمية',
            'P-012' => 'المالية',
            'P-013' => 'المالية/المجلس',
            'P-014' => 'المالية/الرئاسة',
            'P-015' => 'HR',
            'P-016' => 'الدراسات العليا',
            'P-017' => 'المكتبة',
            'P-018' => 'الإدارة/المستشار القانوني',
            'P-019' => 'IT والإدارة',
            'P-020' => 'IT والإدارة',
            'P-021' => 'المالية/IT',
            'P-022' => 'الإدارة',
            'P-023' => 'الإدارة/IT',
            'P-024' => 'المالية',
        ];

        $decisions = [];

        foreach ($owners as $id => $owner) {
            $decisions[] = [
                'id' => $id,
                'owner' => $owner,
                'source' => 'Documentation/Decisions/PENDING_DECISIONS_REGISTER.md',
            ];
        }

        return $decisions;
    }
}
