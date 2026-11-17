<?php

namespace Tests\Feature;

use App\Actions\AssignRole;
use App\Actions\RevokeRole;
use App\DataScopeCode;
use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\DataScope;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class IdentityRbacFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_identity_and_scope_schema_is_created(): void
    {
        $this->assertTrue(Schema::hasTable('data_scopes'));
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('role_user'));
        $this->assertTrue(Schema::hasTable('user_campus_assignments'));
        $this->assertTrue(Schema::hasTable('audit_logs'));

        $this->assertTrue(Schema::hasColumns('roles', ['id', 'code', 'data_scope_id', 'is_system']));
        $this->assertTrue(Schema::hasColumns('role_user', [
            'user_id', 'role_id', 'granted_by', 'valid_from', 'valid_to', 'reason',
        ]));
        $this->assertTrue(Schema::hasColumns('user_campus_assignments', ['user_id', 'campus_id']));
        $this->assertTrue(Schema::hasColumns('audit_logs', [
            'actor_id', 'action', 'subject_type', 'subject_id', 'outcome',
            'correlation_id', 'reason', 'before', 'after',
        ]));
    }

    public function test_reference_seeder_creates_blueprint_roles_and_scopes_with_bilingual_labels(): void
    {
        $this->assertSame(count(RoleCode::cases()), Role::count());
        $this->assertSame(count(DataScopeCode::cases()), DataScope::count());

        App::setLocale('ar');
        $this->assertSame('مدير حرم', RoleCode::CampusManager->label());
        $this->assertSame('الحرم', DataScopeCode::Campus->label());

        App::setLocale('en');
        $this->assertSame('Campus Manager', RoleCode::CampusManager->label());
        $this->assertSame('Institution', DataScopeCode::Institution->label());
    }

    public function test_campus_manager_sees_only_the_assigned_campus(): void
    {
        $institution = Institution::factory()->create();
        $first = Campus::factory()->create([
            'institution_id' => $institution->id,
            'code' => 'CAMPUS-A',
        ]);
        $second = Campus::factory()->create([
            'institution_id' => $institution->id,
            'code' => 'CAMPUS-B',
        ]);

        $manager = $this->userWithRole(RoleCode::CampusManager);
        $manager->campuses()->attach($first);

        $this->assertSame(
            ['CAMPUS-A'],
            Campus::query()->visibleTo($manager)->pluck('code')->all()
        );
        $this->assertTrue(Gate::forUser($manager)->allows('view', $first));
        $this->assertTrue(Gate::forUser($manager)->denies('view', $second));
        $this->assertTrue(
            Campus::query()->whereKey($second->getKey())->visibleTo($manager)->doesntExist()
        );
    }

    public function test_institution_wide_role_sees_all_campuses_without_explicit_assignment(): void
    {
        $institution = Institution::factory()->create();
        Campus::factory()->count(2)->create(['institution_id' => $institution->id]);

        $president = $this->userWithRole(RoleCode::UniversityPresident);

        $this->assertSame(2, Campus::query()->visibleTo($president)->count());
        $this->assertTrue($president->hasInstitutionWideAccess());
    }

    public function test_role_without_campus_scope_and_without_assignment_sees_no_campuses(): void
    {
        $institution = Institution::factory()->create();
        Campus::factory()->create(['institution_id' => $institution->id]);

        $officer = $this->userWithRole(RoleCode::AdmissionsOfficer);

        $this->assertSame(0, Campus::query()->visibleTo($officer)->count());
    }

    public function test_expired_role_does_not_grant_any_visibility(): void
    {
        $institution = Institution::factory()->create();
        $campus = Campus::factory()->create(['institution_id' => $institution->id]);

        $manager = $this->userWithRole(RoleCode::CampusManager);
        $manager->campuses()->attach($campus);
        $manager->roles()->updateExistingPivot(
            $this->role(RoleCode::CampusManager)->getKey(),
            ['valid_to' => now()->subDay()]
        );

        $this->assertFalse($manager->fresh()->hasActiveRole(RoleCode::CampusManager));
        $this->assertSame(0, Campus::query()->visibleTo($manager->fresh())->count());
    }

    public function test_assign_role_writes_a_complete_audit_record(): void
    {
        $actor = $this->userWithRole(RoleCode::IdentityAdmin);
        $target = User::factory()->create();
        $role = $this->role(RoleCode::CampusManager);

        app(AssignRole::class)(
            $target,
            $role,
            $actor,
            'تكليف تشغيلي مؤقت',
            now(),
            now()->addMonth(),
            'CORR-123'
        );

        $this->assertTrue($target->fresh()->hasActiveRole(RoleCode::CampusManager));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'role.assigned',
            'subject_type' => Role::class,
            'subject_id' => (string) $role->id,
            'outcome' => 'success',
            'correlation_id' => 'CORR-123',
            'reason' => 'تكليف تشغيلي مؤقت',
        ]);
        $this->assertSame(
            'campus_manager',
            AuditLog::query()->firstOrFail()->after['role_code']
        );
    }

    public function test_role_assignment_rejects_self_delegation(): void
    {
        $actor = $this->userWithRole(RoleCode::IdentityAdmin);

        $this->expectException(AuthorizationException::class);

        app(AssignRole::class)(
            $actor,
            $this->role(RoleCode::CampusManager),
            $actor,
            'محاولة تفويض ذاتي'
        );
    }

    public function test_role_assignment_rejects_an_actor_without_identity_management_role(): void
    {
        $actor = $this->userWithRole(RoleCode::FacultyMember);
        $target = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(AssignRole::class)(
            $target,
            $this->role(RoleCode::CampusManager),
            $actor,
            'محاولة منح غير مخولة'
        );
    }

    public function test_revoke_role_detaches_and_logs_the_previous_state(): void
    {
        $actor = $this->userWithRole(RoleCode::IdentityAdmin);
        $target = User::factory()->create();
        $role = $this->role(RoleCode::CampusManager);

        app(AssignRole::class)($target, $role, $actor, 'تكليف أولي');
        app(RevokeRole::class)($target, $role, $actor, 'انتهاء التكليف');

        $this->assertFalse($target->fresh()->hasActiveRole(RoleCode::CampusManager));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'role.revoked',
            'actor_id' => $actor->id,
            'reason' => 'انتهاء التكليف',
        ]);
        $this->assertSame(
            'campus_manager',
            AuditLog::query()->where('action', 'role.revoked')->firstOrFail()->before['role_code']
        );
    }

    public function test_expired_role_validity_window_is_rejected(): void
    {
        $actor = $this->userWithRole(RoleCode::IdentityAdmin);
        $target = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        app(AssignRole::class)(
            $target,
            $this->role(RoleCode::CampusManager),
            $actor,
            'نافذة زمنية غير صحيحة',
            now(),
            now()->subDay()
        );
    }

    public function test_audit_log_cannot_be_edited(): void
    {
        $log = AuditLog::factory()->create();

        $this->expectException(LogicException::class);

        $log->update(['outcome' => 'tampered']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = AuditLog::factory()->create();

        $this->expectException(LogicException::class);

        $log->delete();
    }

    private function role(RoleCode $code): Role
    {
        return Role::query()->where('code', $code->value)->firstOrFail();
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($this->role($code));

        return $user->refresh();
    }
}
