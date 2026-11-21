<?php

namespace Tests\Feature;

use App\Actions\AssignRole;
use App\Actions\DryRunReferenceImport;
use App\DataScopeCode;
use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\DataScope;
use App\Models\Role;
use App\Models\StudentInvoice;
use App\Models\User;
use App\RoleCode;
use App\Support\DataIntegrityAudit;
use App\Support\ReferenceDataCatalog;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class QaAndMigrationReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_every_migration_table_exists_and_integrity_snapshot_is_clean(): void
    {
        $tables = [];

        foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
            $contents = file_get_contents($file);

            if ($contents !== false
                && preg_match("/Schema::create\(\s*['\"]([^'\"]+)['\"]/", $contents, $matches) === 1) {
                $tables[] = $matches[1];
            }
        }

        $this->assertNotSame([], $tables);

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "الجدول الناقص: {$table}");
        }

        $invoice = StudentInvoice::factory()->create();

        $orphans = array_values(array_filter(
            DataIntegrityAudit::orphanChecks(),
            fn (array $check): bool => $check['count'] > 0,
        ));
        $duplicates = array_values(array_filter(
            DataIntegrityAudit::duplicateChecks(),
            fn (array $check): bool => $check['duplicates'] > 0,
        ));

        $this->assertSame([], $orphans);
        $this->assertSame([], $duplicates);
        $this->assertNotSame([], DataIntegrityAudit::immutableModels());

        try {
            DB::table('students')->where('id', $invoice->student_id)->delete();
            $this->fail('حذف طالب مرتبط بفاتورة يجب أن يُرفض على مستوى قاعدة البيانات.');
        } catch (QueryException) {
            // متوقع: قيد FK يمنع الحذف الصامت.
        }
    }

    public function test_reference_catalog_is_closed_and_matches_approved_counts(): void
    {
        $this->assertSame(['data_scopes', 'roles'], array_keys(ReferenceDataCatalog::sets()));
        $expected = ReferenceDataCatalog::expectedCounts();

        $this->assertSame(count(DataScopeCode::cases()), $expected['data_scopes']);
        $this->assertSame(count(RoleCode::cases()), $expected['roles']);
        $this->assertSame(DataScope::count(), $expected['data_scopes']);
        $this->assertSame(Role::count(), $expected['roles']);

        try {
            ReferenceDataCatalog::set('students');
            $this->fail('مجموعة غير معتمدة يجب أن تُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }
    }

    public function test_dry_run_import_validates_rows_without_writing_anything(): void
    {
        $admin = $this->userWithRole(RoleCode::SystemAdmin);
        Role::query()->where('code', RoleCode::ReportReader->value)->delete();
        $before = Role::count();

        $result = (new DryRunReferenceImport)($admin, 'roles', [
            ['code' => RoleCode::ReportReader->value, 'data_scope' => null, 'is_system' => true],
            ['code' => RoleCode::SystemAdmin->value],
            ['code' => DataScopeCode::Campus->value, 'unexpected' => 1],
            ['code' => 'not_a_approved_role'],
            ['code' => RoleCode::ReportReader->value],
            ['code' => RoleCode::Student->value, 'data_scope' => 'not_a_scope'],
        ]);

        $this->assertSame('roles', $result['set']);
        $this->assertSame(0, $result['writes_performed']);
        $this->assertSame($before, Role::count());
        $this->assertSame(1, $result['would_insert']);
        $this->assertSame(1, $result['would_skip']);
        $this->assertSame('insert', $result['accepted'][0]['action']);
        $this->assertSame('skip', $result['accepted'][1]['action']);
        $this->assertCount(4, $result['errors']);
    }

    public function test_dry_run_import_rejects_unauthorized_actors_and_unknown_sets(): void
    {
        $outsider = $this->userWithRole(RoleCode::FacilitiesOfficer);
        $admin = $this->userWithRole(RoleCode::SystemAdmin);
        $action = new DryRunReferenceImport;

        $unauthorizedDenied = false;

        try {
            $action($outsider, 'roles', []);
        } catch (AuthorizationException) {
            $unauthorizedDenied = true;
        }

        $unknownSetDenied = false;

        try {
            $action($admin, 'student_grades', []);
        } catch (InvalidArgumentException) {
            $unknownSetDenied = true;
        }

        $invalidShapeDenied = false;

        try {
            $action($admin, 'roles', ['code' => RoleCode::Student->value]);
        } catch (InvalidArgumentException) {
            $invalidShapeDenied = true;
        }

        $this->assertTrue($unauthorizedDenied, 'فحص استيراد من غير مخول يجب أن يُرفض.');
        $this->assertTrue($unknownSetDenied, 'مجموعة غير معتمدة يجب أن تُرفض.');
        $this->assertTrue($invalidShapeDenied, 'الصفوف غير القائمة يجب أن تُرفض.');
    }

    public function test_validation_command_is_read_only_and_reports_blocking_errors(): void
    {
        $this->userWithRole(RoleCode::SystemAdmin);
        $before = DataScope::count();
        $path = tempnam(sys_get_temp_dir(), 'ums-qa-');

        $this->assertNotFalse($path);
        file_put_contents($path, json_encode([
            ['code' => DataScopeCode::Campus->value],
            ['code' => 'not_an_approved_scope'],
        ], JSON_UNESCAPED_UNICODE));

        $exit = Artisan::call('qa:validate-reference-import', [
            'file' => $path,
            '--set' => 'data_scopes',
        ]);

        $output = Artisan::output();
        unlink($path);

        $this->assertSame(Command::FAILURE, $exit);
        $this->assertStringContainsString('غير معتمد', $output);
        $this->assertSame($before, DataScope::count());
    }

    public function test_cross_module_scope_and_delegation_checks_fail_closed_without_audit_leaks(): void
    {
        $firstCampus = Campus::factory()->create();
        $secondCampus = Campus::factory()->create();
        $manager = $this->userWithRole(RoleCode::CampusManager);
        $manager->campuses()->attach($firstCampus);
        $manager->refresh();

        $this->assertSame([$firstCampus->id], Campus::query()->visibleTo($manager)->pluck('id')->all());
        $this->assertFalse(Campus::query()->whereKey($secondCampus->id)->visibleTo($manager)->exists());

        try {
            (new DryRunReferenceImport)($manager, 'roles', []);
            $this->fail('فحص الاستيراد من مدير الحرم يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $admin = $this->userWithRole(RoleCode::SystemAdmin);
        $target = User::factory()->create();
        $reportReader = Role::query()->where('code', RoleCode::ReportReader->value)->firstOrFail();

        (new AssignRole)($target, $reportReader, $admin, 'فحص تدقيق');

        $expired = User::factory()->create();
        (new AssignRole)(
            $expired, $reportReader, $admin, 'تفويض منتهي', null, now()->subDay(),
        );

        $this->assertFalse($expired->fresh()->hasActiveRole(RoleCode::ReportReader));

        try {
            (new DryRunReferenceImport)($expired->fresh(), 'roles', []);
            $this->fail('التفويض المنتهي يجب ألا يمنح صلاحية.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $payload = AuditLog::query()->get()->toJson();
        $this->assertStringNotContainsString($target->password, $payload);
        $this->assertStringNotContainsString('password', $payload);
        $this->assertStringNotContainsString('secret', $payload);
    }

    public function test_audit_records_cannot_be_changed_or_removed(): void
    {
        $admin = $this->userWithRole(RoleCode::SystemAdmin);
        $target = User::factory()->create();
        $role = Role::query()->where('code', RoleCode::ReportReader->value)->firstOrFail();

        (new AssignRole)($target, $role, $admin, 'فحص ثبات السجل');
        $audit = AuditLog::query()->firstOrFail();

        $updateDenied = false;

        try {
            $audit->update(['outcome' => 'tampered']);
        } catch (LogicException) {
            $updateDenied = true;
        }

        $deleteDenied = false;

        try {
            AuditLog::query()->firstOrFail()->delete();
        } catch (LogicException) {
            $deleteDenied = true;
        }

        $this->assertTrue($updateDenied, 'تعديل سجل التدقيق يجب أن يُرفض.');
        $this->assertTrue($deleteDenied, 'حذف سجل التدقيق يجب أن يُرفض.');
        $this->assertSame(1, AuditLog::query()->count());
    }

    private function userWithRole(RoleCode $code, ?User $user = null): User
    {
        $user ??= User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
