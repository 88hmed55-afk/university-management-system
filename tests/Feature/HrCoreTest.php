<?php

namespace Tests\Feature;

use App\Actions\AssignEmployee;
use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\College;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentAssignment;
use App\Models\FacultyProfile;
use App\Models\Institution;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\RecordStatus;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class HrCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_hr_core_schema_is_created(): void
    {
        foreach (['employees', 'faculty_profiles', 'employment_assignments'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('employees', [
            'person_id', 'employee_no', 'employment_type_code', 'job_title', 'status', 'archived_at',
        ]));
        $this->assertTrue(Schema::hasColumns('faculty_profiles', [
            'employee_id', 'specialization_code', 'rank_code', 'department_id',
        ]));
        $this->assertTrue(Schema::hasColumns('employment_assignments', [
            'employee_id', 'manager_employee_id', 'supervisor_employee_id', 'college_id',
            'department_id', 'campus_id', 'valid_from', 'valid_to', 'decision_reference', 'approved_by',
        ]));
    }

    public function test_employee_is_one_per_person_and_employee_number_is_unique(): void
    {
        $person = Person::factory()->create();
        $employee = Employee::factory()->create(['person_id' => $person->id, 'employee_no' => 'EMP-0001']);

        $this->assertTrue($employee->person->is($person));
        $this->assertSame(1, Employee::query()->where('person_id', $person->id)->count());

        try {
            Employee::factory()->create(['person_id' => $person->id]);
            $this->fail('الشخص الواحد لا يمكن أن يكون له أكثر من ملف موظف.');
        } catch (QueryException) {
            // متوقع
        }

        $this->expectException(QueryException::class);

        Employee::factory()->create(['employee_no' => 'EMP-0001']);
    }

    public function test_faculty_profile_is_single_and_has_no_invented_rank_or_specialization(): void
    {
        $employee = Employee::factory()->create();
        $profile = FacultyProfile::factory()->create(['employee_id' => $employee->id]);

        $this->assertTrue($profile->employee->is($employee));
        $this->assertTrue($employee->facultyProfile->is($profile));
        $this->assertNull($profile->rank_code);
        $this->assertNull($profile->specialization_code);
    }

    public function test_assignment_carries_dependency_placement_and_decision_reference(): void
    {
        $employee = Employee::factory()->create();
        $manager = Employee::factory()->create();
        $supervisor = Employee::factory()->create();
        $campus = Campus::factory()->create();
        $college = College::factory()->create();
        $department = Department::factory()->create();

        $assignment = EmploymentAssignment::factory()->create([
            'employee_id' => $employee->id,
            'manager_employee_id' => $manager->id,
            'supervisor_employee_id' => $supervisor->id,
            'campus_id' => $campus->id,
            'college_id' => $college->id,
            'department_id' => $department->id,
            'valid_from' => '2026-01-01',
        ]);

        $this->assertTrue($assignment->employee->is($employee));
        $this->assertTrue($assignment->manager->is($manager));
        $this->assertTrue($assignment->supervisor->is($supervisor));
        $this->assertTrue($assignment->campus->is($campus));
        $this->assertNotEmpty($assignment->decision_reference);
        $this->assertSame([$assignment->id], EmploymentAssignment::query()->currentlyValid()->pluck('id')->all());
    }

    public function test_one_direct_manager_per_overlapping_period_but_parallel_supervision_is_allowed(): void
    {
        $employee = Employee::factory()->create();
        $manager = Employee::factory()->create();
        $supervisorA = Employee::factory()->create();
        $supervisorB = Employee::factory()->create();

        EmploymentAssignment::factory()->create([
            'employee_id' => $employee->id,
            'manager_employee_id' => $manager->id,
            'supervisor_employee_id' => $supervisorA->id,
            'valid_from' => '2026-01-01',
        ]);

        $parallel = EmploymentAssignment::factory()->create([
            'employee_id' => $employee->id,
            'manager_employee_id' => $manager->id,
            'supervisor_employee_id' => $supervisorB->id,
            'valid_from' => '2026-03-01',
        ]);

        $this->assertTrue($parallel->supervisor->is($supervisorB));
        $this->assertSame(2, $employee->assignments()->count());

        $this->expectException(InvalidArgumentException::class);

        EmploymentAssignment::factory()->create([
            'employee_id' => $employee->id,
            'manager_employee_id' => Employee::factory()->create()->id,
            'valid_from' => '2026-04-01',
        ]);
    }

    public function test_closed_period_allows_a_new_manager_without_conflict(): void
    {
        $employee = Employee::factory()->create();
        $oldManager = Employee::factory()->create();
        $newManager = Employee::factory()->create();

        EmploymentAssignment::factory()->create([
            'employee_id' => $employee->id,
            'manager_employee_id' => $oldManager->id,
            'valid_from' => '2025-01-01',
            'valid_to' => '2025-12-31',
        ]);

        $assignment = EmploymentAssignment::factory()->create([
            'employee_id' => $employee->id,
            'manager_employee_id' => $newManager->id,
            'valid_from' => '2026-01-01',
        ]);

        $this->assertTrue($assignment->manager->is($newManager));
    }

    public function test_self_dependency_and_invalid_window_are_rejected(): void
    {
        $employee = Employee::factory()->create();

        try {
            EmploymentAssignment::factory()->create([
                'employee_id' => $employee->id,
                'manager_employee_id' => $employee->id,
            ]);
            $this->fail('المدير الذاتي يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $this->expectException(InvalidArgumentException::class);

        EmploymentAssignment::factory()->create([
            'employee_id' => $employee->id,
            'valid_from' => '2026-06-01',
            'valid_to' => '2026-01-01',
        ]);
    }

    public function test_hr_records_cannot_be_deleted_and_are_closed_by_dates(): void
    {
        $rows = [
            Employee::factory()->create(),
            FacultyProfile::factory()->create(),
            EmploymentAssignment::factory()->create(),
        ];

        foreach ($rows as $row) {
            try {
                $row->delete();
                $this->fail('حذف سجلات الموارد البشرية يجب أن يُرفض.');
            } catch (LogicException) {
                // متوقع
            }

            $this->assertNotNull($row->fresh());
        }

        $assignment = $rows[2];
        $assignment->valid_to = '2026-12-31';
        $assignment->status = RecordStatus::Archived;
        $assignment->archived_at = now();
        $assignment->save();

        $this->assertDatabaseHas('employment_assignments', ['id' => $assignment->id]);
        $this->assertSame(0, EmploymentAssignment::query()->currentlyValid()->count());
    }

    public function test_hr_actor_can_assign_and_the_action_is_audited(): void
    {
        $employee = Employee::factory()->create();
        $manager = Employee::factory()->create();
        $actor = $this->userWithRole(RoleCode::HrOfficer);

        $assignment = (new AssignEmployee)(
            employee: $employee,
            actor: $actor,
            validFrom: Carbon::parse('2026-01-01'),
            validTo: null,
            decisionReference: 'DEC-APPROVED-1',
            manager: $manager,
            reason: 'تعيين',
        );

        $this->assertTrue($assignment->employee->is($employee));
        $this->assertTrue($assignment->manager->is($manager));
        $this->assertSame($actor->id, $assignment->approved_by);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'employment_assignment.created',
            'outcome' => 'success',
        ]);
    }

    public function test_campus_manager_cannot_change_assignments(): void
    {
        $employee = Employee::factory()->create();
        $actor = $this->userWithRole(RoleCode::CampusManager);
        $before = EmploymentAssignment::count();

        try {
            (new AssignEmployee)(
                employee: $employee,
                actor: $actor,
                validFrom: Carbon::parse('2026-01-01'),
                validTo: null,
                decisionReference: 'DEC-NO',
            );
            $this->fail('مدير الحرم لا يجوز له تغيير التعيينات.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertSame($before, EmploymentAssignment::count());
        $this->assertSame(0, AuditLog::count());
    }

    public function test_assignment_requires_a_decision_reference(): void
    {
        $employee = Employee::factory()->create();
        $actor = $this->userWithRole(RoleCode::HrDirector);

        $this->expectException(InvalidArgumentException::class);

        (new AssignEmployee)(
            employee: $employee,
            actor: $actor,
            validFrom: Carbon::parse('2026-01-01'),
            validTo: null,
            decisionReference: '   ',
        );
    }

    public function test_assignment_visibility_is_scoped_to_the_assigned_campus(): void
    {
        $institution = Institution::factory()->create();
        $ownedCampus = Campus::factory()->create(['institution_id' => $institution->id]);
        $otherCampus = Campus::factory()->create(['institution_id' => $institution->id]);
        $owned = EmploymentAssignment::factory()->create(['campus_id' => $ownedCampus->id]);
        $other = EmploymentAssignment::factory()->create(['campus_id' => $otherCampus->id]);

        $manager = $this->userWithRole(RoleCode::CampusManager);
        $manager->campuses()->attach($ownedCampus);

        $president = $this->userWithRole(RoleCode::UniversityPresident);
        $noRole = User::factory()->create();

        $this->assertSame([$owned->id], EmploymentAssignment::query()->visibleTo($manager)->pluck('id')->all());
        $this->assertSame(2, EmploymentAssignment::query()->visibleTo($president)->count());
        $this->assertSame(0, EmploymentAssignment::query()->visibleTo($noRole)->count());
        $this->assertSame([$owned->employee_id], Employee::query()->visibleTo($manager)->pluck('id')->all());
        $this->assertFalse(Employee::query()->whereKey($other->employee_id)->visibleTo($manager)->exists());
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
