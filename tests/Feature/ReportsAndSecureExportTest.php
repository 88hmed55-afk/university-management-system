<?php

namespace Tests\Feature;

use App\Actions\ApproveReportDefinition;
use App\Actions\ArchiveDashboard;
use App\Actions\ArchiveReportDefinition;
use App\Actions\DefineDashboard;
use App\Actions\DefineReportDefinition;
use App\Actions\ExportReport;
use App\Actions\ListDashboardsForActor;
use App\Actions\RunReport;
use App\Actions\SaveReportView;
use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\LibraryBranch;
use App\Models\Person;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\ReportSavedView;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class ReportsAndSecureExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_report_schema_and_history_records_are_immutable(): void
    {
        foreach ([
            'report_definitions', 'report_dashboards', 'report_runs',
            'report_saved_views', 'report_exports',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('report_definitions', [
            'source_code', 'metric_definition', 'metric_reference', 'scope_code',
            'max_rows', 'max_rows_reference', 'allowed_role_codes', 'status',
        ]));
        $this->assertTrue(Schema::hasColumns('report_exports', [
            'format_code', 'storage_key', 'checksum', 'filters', 'scope_snapshot',
            'include_pii', 'idempotency_key', 'status',
        ]));

        $definition = $this->approvedDefinition('campuses', 'institution');

        try {
            $definition->update(['code' => 'RPT-CHANGED']);
            $this->fail('تعديل رمز تعريف التقرير يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $definition->delete();
            $this->fail('حذف تعريف التقرير يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        foreach ([
            ReportRun::factory()->create(),
            ReportSavedView::factory()->create(),
            ReportExport::factory()->create(),
        ] as $record) {
            try {
                $record->delete();
                $this->fail('حذف سجلات التقارير يجب أن يُرفض.');
            } catch (LogicException) {
                // متوقع
            }
        }
    }

    public function test_definitions_require_references_and_separate_approval(): void
    {
        $creator = $this->userWithRole(RoleCode::SystemAdmin);
        $approver = $this->userWithRole(RoleCode::UniversityPresident);
        $outsider = $this->userWithRole(RoleCode::FacilitiesOfficer);
        $define = new DefineReportDefinition;

        $base = [
            'actor' => $creator,
            'code' => 'RPT-DETAIL',
            'name' => 'تقرير مفصل',
            'owner' => $creator,
            'sourceCode' => 'campuses',
            'metricDefinition' => 'عدد الأحرم النشطة',
            'metricReference' => 'METRIC-1',
            'scopeCode' => 'institution',
            'allowedRoleCodes' => [RoleCode::UniversityPresident->value],
            'allowedExportRoleCodes' => [RoleCode::UniversityPresident->value],
            'filterDefinition' => ['code'],
            'maxRows' => 25,
            'maxRowsReference' => 'CAP-1',
            'decisionReference' => 'DEC-1',
        ];

        try {
            $define(...[...$base, 'actor' => $outsider, 'code' => 'RPT-X']);
            $this->fail('تعريف تقرير من غير مخول يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        try {
            $define(...[...$base, 'sourceCode' => 'users_passwords', 'code' => 'RPT-X']);
            $this->fail('مصدر بيانات غير مسموح يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $define(...[...$base, 'scopeCode' => 'campus', 'code' => 'RPT-X']);
            $this->fail('نطاق لا يطابق المصدر يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $define(...[...$base, 'freshnessCode' => 'periodic', 'code' => 'RPT-X']);
            $this->fail('تحديث دوري بلا مرجع جدولة يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $define(...[...$base, 'includeDrafts' => true, 'code' => 'RPT-X']);
            $this->fail('إدخال مسودات بلا مرجع قرار يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $definition = $define(...$base);
        $this->assertSame('draft', $definition->status);

        try {
            (new ApproveReportDefinition)($definition, $creator, 'APP-1');
            $this->fail('اعتماد المنشئ لتعريفه يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $approved = (new ApproveReportDefinition)($definition, $approver, 'APP-1');
        $this->assertSame('approved', $approved->status);

        try {
            $approved->update(['metric_definition' => 'مؤشر مختلف']);
            $this->fail('تعديل تعريف مؤشر معتمد يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $archived = (new ArchiveReportDefinition)($approved->fresh(), $approver, 'لم يعد مستخدماً', 'DEC-ARCH');
        $this->assertSame('archived', $archived->status);

        foreach ([
            'reports.definition_defined', 'reports.definition_approved', 'reports.definition_archived',
        ] as $action) {
            $this->assertTrue(AuditLog::query()->where('action', $action)->exists(), "missing audit: {$action}");
        }
    }

    public function test_campus_report_and_dashboard_follow_the_actor_scope(): void
    {
        $firstCampus = Campus::factory()->create();
        $secondCampus = Campus::factory()->create();
        $firstBranch = LibraryBranch::factory()->create(['campus_id' => $firstCampus->id]);
        LibraryBranch::factory()->create(['campus_id' => $secondCampus->id]);

        $manager = $this->userWithRole(RoleCode::CampusManager);
        $manager->campuses()->attach($firstCampus);
        $manager->refresh();
        $president = $this->userWithRole(RoleCode::UniversityPresident);
        $definition = $this->approvedDefinition(
            'library_branches', 'campus',
            [RoleCode::CampusManager->value, RoleCode::UniversityPresident->value],
            [RoleCode::UniversityPresident->value],
            ['status'],
        );

        $managerRun = (new RunReport)($manager, $definition, 'RUN-MANAGER');
        $presidentRun = (new RunReport)($president, $definition, 'RUN-PRESIDENT');

        $this->assertSame([$firstBranch->id], array_column($managerRun['rows'], 'id'));
        $this->assertCount(2, $presidentRun['rows']);
        $this->assertSame($presidentRun['checksum'], $presidentRun['run']->checksum);
        $this->assertTrue(AuditLog::query()->where('action', 'reports.run')->exists());

        $governance = $this->userWithRole(RoleCode::SystemAdmin);
        $dashboard = (new DefineDashboard)(
            $governance, 'DASH-CAMPUS', 'لوحة الحرم', RoleCode::CampusManager->value,
            'campus', [$definition->id], 'DEC-DASH',
        );

        $visible = (new ListDashboardsForActor)($manager);
        $this->assertTrue($visible->contains(fn ($item): bool => $item->id === $dashboard->id));
        $this->assertCount(0, (new ListDashboardsForActor)($president));

        foreach ($visible as $item) {
            foreach ($item->definition_ids as $definitionId) {
                $fromDashboard = (new RunReport)($manager, ReportDefinition::findOrFail($definitionId), 'RUN-DASH-'.Str::uuid());
                $this->assertSame($managerRun['rows'], $fromDashboard['rows']);
            }
        }

        try {
            $dashboard->delete();
            $this->fail('حذف لوحة التقارير يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        (new ArchiveDashboard)($dashboard, $governance, 'إلغاء اللوحة', 'DEC-ARCH-DASH');
        $this->assertSame('archived', $dashboard->fresh()->status);
    }

    public function test_self_scope_uses_own_records_and_approved_rows_only(): void
    {
        $person = Person::factory()->create(['given_name' => 'أحمد', 'family_name' => 'المختبر']);
        $studentUser = User::factory()->create(['person_id' => $person->id]);
        $this->userWithRole(RoleCode::Student, $studentUser);
        $student = Student::factory()->create(['person_id' => $person->id]);
        $otherStudent = Student::factory()->create();

        StudentInvoice::factory()->create(['student_id' => $student->id, 'status' => 'issued', 'invoice_no' => 'INV-OWN']);
        StudentInvoice::factory()->create(['student_id' => $student->id, 'status' => 'draft', 'invoice_no' => 'INV-DRAFT']);
        StudentInvoice::factory()->create(['student_id' => $otherStudent->id, 'status' => 'issued', 'invoice_no' => 'INV-OTHER']);

        $approvedOnly = $this->approvedDefinition(
            'invoices_self', 'self',
            [RoleCode::Student->value], [RoleCode::Student->value],
            ['status'],
        );
        $withDrafts = $this->approvedDefinition(
            'invoices_self', 'self',
            [RoleCode::Student->value], [RoleCode::Student->value],
            ['status'], true,
        );

        $result = (new RunReport)($studentUser, $approvedOnly, 'RUN-SELF');
        $this->assertSame(['INV-OWN'], array_column($result['rows'], 'invoice_no'));

        $withDraftRows = (new RunReport)($studentUser, $withDrafts, 'RUN-DRAFTS');
        $this->assertSame(['INV-OWN', 'INV-DRAFT'], array_column($withDraftRows['rows'], 'invoice_no'));

        try {
            (new RunReport)($studentUser, $approvedOnly, 'RUN-BAD-FILTER', ['student_id' => $student->id]);
            $this->fail('فلتر غير مسموح يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $personless = $this->userWithRole(RoleCode::Student);

        try {
            (new RunReport)($personless, $approvedOnly, 'RUN-NO-PERSON');
            $this->fail('تشغيل تقرير ذاتي بلا هوية شخص يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }
    }

    public function test_saved_views_are_private_and_do_not_widen_scope(): void
    {
        $campus = Campus::factory()->create();
        $otherCampus = Campus::factory()->create();
        LibraryBranch::factory()->create(['campus_id' => $campus->id]);
        LibraryBranch::factory()->create(['campus_id' => $otherCampus->id]);

        $manager = $this->userWithRole(RoleCode::CampusManager);
        $manager->campuses()->attach($campus);
        $manager->refresh();
        $otherManager = $this->userWithRole(RoleCode::CampusManager);
        $otherManager->campuses()->attach($otherCampus);
        $otherManager->refresh();

        $definition = $this->approvedDefinition(
            'library_branches', 'campus',
            [RoleCode::CampusManager->value], [RoleCode::CampusManager->value],
            ['campus_id'],
        );

        $view = (new SaveReportView)(
            $manager, $definition, 'عرض الحرم', ['campus_id' => $otherCampus->id],
        );
        $this->assertSame($manager->id, $view->actor_id);
        $this->assertCount(0, ReportSavedView::query()->where('actor_id', $otherManager->id)->get());

        $narrowed = (new RunReport)($manager, $definition, 'RUN-VIEW', $view->filters);
        $this->assertCount(0, $narrowed['rows']);

        try {
            $view->update(['scope_snapshot' => ['scope_code' => 'institution']]);
            $this->fail('تعديل نطاق العرض المحفوظ يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->assertTrue(AuditLog::query()->where('action', 'reports.view_saved')->exists());
    }

    public function test_export_requires_authority_and_is_audited(): void
    {
        $campus = Campus::factory()->create();
        LibraryBranch::factory()->create(['campus_id' => $campus->id]);
        $reader = $this->userWithRole(RoleCode::ReportReader);
        $this->userWithRole(RoleCode::AcademicLeadership, $reader);
        $president = $this->userWithRole(RoleCode::UniversityPresident);
        $definition = $this->approvedDefinition(
            'campuses', 'institution',
            [RoleCode::ReportReader->value, RoleCode::UniversityPresident->value],
            [RoleCode::UniversityPresident->value],
            ['code'],
        );
        $export = new ExportReport;

        try {
            $export($reader, $definition, 'EXP-NO', 'csv', 10, 'CAP-1');
            $this->fail('تصدير بغير صلاحية صريحة يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertTrue(AuditLog::query()->where('action', 'reports.export_refused')->exists());

        $completed = $export(
            $president, $definition, 'EXP-1', 'csv', 10, 'CAP-1',
            ['code' => $campus->code], false, null, 'تقرير دوري', 'IDEM-1',
        );
        $this->assertSame('completed', $completed->status);
        $this->assertTrue(Storage::disk('local')->exists($completed->storage_key));
        $this->assertSame(hash('sha256', Storage::disk('local')->get($completed->storage_key)), $completed->checksum);

        $audit = AuditLog::query()->where('action', 'reports.export')->firstOrFail();
        $this->assertSame(['code'], $audit->after['filter_keys']);
        $this->assertSame('institution', $audit->after['scope_code']);
        $this->assertNotNull($audit->after['exported_at']);
        $this->assertSame('CAP-1', $audit->after['limit_reference']);

        $again = $export(
            $president, $definition, 'EXP-2', 'csv', 10, 'CAP-1',
            ['code' => $campus->code], false, null, null, 'IDEM-1',
        );
        $third = $export(
            $president, $definition, 'EXP-3', 'csv', 10, 'CAP-1',
            ['code' => $campus->code], false, null, null, 'IDEM-1',
        );
        $this->assertSame($again->id, $third->id);
        $this->assertSame(1, AuditLog::query()->where('action', 'reports.export')->count());

        try {
            $completed->delete();
            $this->fail('حذف سجل تصدير يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_export_masks_pii_until_an_explicit_basis_is_supplied(): void
    {
        $person = Person::factory()->create(['given_name' => 'أحمد', 'family_name' => 'المختبر']);
        $studentUser = User::factory()->create(['person_id' => $person->id]);
        $this->userWithRole(RoleCode::Student, $studentUser);
        Student::factory()->create(['person_id' => $person->id]);
        $definition = $this->approvedDefinition(
            'students_self', 'self',
            [RoleCode::Student->value], [RoleCode::Student->value],
            [],
        );
        $export = new ExportReport;

        $masked = $export($studentUser, $definition, 'EXP-MASK', 'csv', 10, 'CAP-PII');
        $maskedContent = Storage::disk('local')->get($masked->storage_key);
        $this->assertStringContainsString('***', $maskedContent);
        $this->assertStringNotContainsString('المختبر', $maskedContent);
        $this->assertFalse($masked->include_pii);

        try {
            $export($studentUser, $definition, 'EXP-BASIS', 'csv', 10, 'CAP-PII', [], true);
            $this->fail('إظهار PII بلا مرجع أساس يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $revealed = $export(
            $studentUser, $definition, 'EXP-NAMED', 'csv', 10, 'CAP-PII',
            [], true, 'LEGAL-BASIS-1',
        );
        $this->assertStringContainsString('المختبر', Storage::disk('local')->get($revealed->storage_key));
        $this->assertTrue($revealed->include_pii);
        $this->assertSame('LEGAL-BASIS-1', $revealed->pii_basis_reference);
    }

    public function test_export_enforces_declared_caps_and_rejects_unapproved_formats(): void
    {
        $president = $this->userWithRole(RoleCode::UniversityPresident);
        $definition = $this->approvedDefinition(
            'campuses', 'institution',
            [RoleCode::UniversityPresident->value], [RoleCode::UniversityPresident->value],
            [], false, 3,
        );
        $export = new ExportReport;

        foreach ([
            fn () => $export($president, $definition, 'EXP-BIG', 'csv', 4, 'CAP-3'),
            fn () => $export($president, $definition, 'EXP-NOCAP', 'csv', 3, ''),
            fn () => $export($president, $definition, 'EXP-PDF', 'pdf', 3, 'CAP-3'),
            fn () => $export($president, $definition, 'EXP-XLSX', 'xlsx', 3, 'CAP-3'),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('محاولة تصدير غير مطابقة للقيود يجب أن تُرفض.');
            } catch (AuthorizationException) {
                // متوقع
            }
        }

        $this->assertSame(4, AuditLog::query()->where('action', 'reports.export_refused')->count());
        $this->assertSame(0, ReportExport::count());
        $this->assertTrue(AuditLog::query()
            ->where('action', 'reports.export_refused')
            ->where('reason', 'like', '%P-022%')
            ->exists());
    }

    private function approvedDefinition(
        string $sourceCode,
        string $scopeCode,
        ?array $runRoles = null,
        ?array $exportRoles = null,
        array $filters = [],
        bool $includeDrafts = false,
        int $maxRows = 50,
    ): ReportDefinition {
        $creator = $this->userWithRole(RoleCode::SystemAdmin);
        $approver = $this->userWithRole(RoleCode::UniversityPresident);

        $definition = (new DefineReportDefinition)(
            actor: $creator,
            code: 'RPT-'.Str::uuid()->toString(),
            name: 'تقرير اختبار',
            owner: $creator,
            sourceCode: $sourceCode,
            metricDefinition: 'عدد السجلات المعتمدة في المصدر المحدد.',
            metricReference: 'METRIC-'.Str::uuid()->toString(),
            scopeCode: $scopeCode,
            allowedRoleCodes: $runRoles ?? [RoleCode::UniversityPresident->value, RoleCode::ReportReader->value],
            allowedExportRoleCodes: $exportRoles ?? [RoleCode::UniversityPresident->value, RoleCode::InternalAuditor->value],
            filterDefinition: $filters,
            maxRows: $maxRows,
            maxRowsReference: 'CAP-'.Str::uuid()->toString(),
            decisionReference: 'DEC-'.Str::uuid()->toString(),
            includeDrafts: $includeDrafts,
            includeDraftsReference: $includeDrafts ? 'DRAFT-'.Str::uuid()->toString() : null,
        );

        (new ApproveReportDefinition)($definition, $approver, 'APP-'.Str::uuid()->toString());

        return $definition->refresh();
    }

    private function userWithRole(RoleCode $code, ?User $user = null): User
    {
        $user ??= User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
