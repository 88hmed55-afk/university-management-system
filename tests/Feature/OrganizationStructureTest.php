<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\College;
use App\Models\Department;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrganizationStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_organization_schema_is_created(): void
    {
        foreach ([
            'colleges', 'departments', 'campus_college_assignments',
            'department_college_assignments', 'user_college_assignments',
            'user_department_assignments',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('campus_college_assignments', [
            'campus_id', 'college_id', 'effective_from', 'effective_to', 'decision_reference',
        ]));
        $this->assertTrue(Schema::hasColumns('department_college_assignments', [
            'department_id', 'college_id', 'effective_from', 'effective_to', 'decision_reference',
        ]));
        $this->assertTrue(Schema::hasColumns('user_college_assignments', [
            'user_id', 'college_id', 'valid_from', 'valid_to', 'reason', 'approved_by', 'decision_reference',
        ]));
    }

    public function test_a_college_can_operate_on_several_campuses_without_duplicated_effective_dates(): void
    {
        $institution = Institution::factory()->create();
        $first = Campus::factory()->create(['institution_id' => $institution->id]);
        $second = Campus::factory()->create(['institution_id' => $institution->id]);
        $college = College::factory()->create();

        $college->campuses()->attach($first, ['effective_from' => '2026-01-01']);
        $college->campuses()->attach($second, ['effective_from' => '2026-01-01']);

        $this->assertSame(2, $college->campuses()->count());

        $this->expectException(QueryException::class);

        $college->campuses()->attach($first, ['effective_from' => '2026-01-01']);
    }

    public function test_closing_a_campus_location_keeps_its_history(): void
    {
        $campus = Campus::factory()->create();
        $college = College::factory()->create();

        $college->campuses()->attach($campus, ['effective_from' => '2026-01-01']);
        $college->campuses()->updateExistingPivot($campus->getKey(), ['effective_to' => '2026-06-30']);

        $this->assertDatabaseHas('campus_college_assignments', [
            'campus_id' => $campus->id,
            'college_id' => $college->id,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-06-30',
        ]);
        $this->assertSame(1, $college->campuses()->count());
    }

    public function test_department_ownership_is_dated_and_the_old_college_is_not_deleted(): void
    {
        $firstCollege = College::factory()->create();
        $secondCollege = College::factory()->create();
        $department = Department::factory()->create();

        $department->colleges()->attach($firstCollege, [
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-05-31',
        ]);
        $department->colleges()->attach($secondCollege, ['effective_from' => '2026-06-01']);

        $this->assertTrue($department->fresh()->currentCollege()->is($secondCollege));
        $this->assertSame(2, $department->colleges()->count());
        $this->assertDatabaseHas('department_college_assignments', [
            'department_id' => $department->id,
            'college_id' => $firstCollege->id,
            'effective_to' => '2026-05-31',
        ]);
    }

    public function test_college_dean_sees_only_the_assigned_college_and_its_departments(): void
    {
        $ownedCollege = College::factory()->create();
        $otherCollege = College::factory()->create();
        $ownedDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $ownedDepartment->colleges()->attach($ownedCollege, ['effective_from' => '2026-01-01']);
        $otherDepartment->colleges()->attach($otherCollege, ['effective_from' => '2026-01-01']);

        $dean = $this->userWithRole(RoleCode::CollegeDean);
        $dean->assignedColleges()->attach($ownedCollege, ['valid_from' => '2026-01-01', 'reason' => 'تكليف']);

        $this->assertSame(
            [$ownedCollege->id],
            College::query()->visibleTo($dean)->pluck('id')->all()
        );
        $this->assertSame(
            [$ownedDepartment->id],
            Department::query()->visibleTo($dean)->pluck('id')->all()
        );
    }

    public function test_department_head_sees_only_the_assigned_department_and_its_college(): void
    {
        $ownedCollege = College::factory()->create();
        $otherCollege = College::factory()->create();
        $ownedDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $ownedDepartment->colleges()->attach($ownedCollege, ['effective_from' => '2026-01-01']);
        $otherDepartment->colleges()->attach($otherCollege, ['effective_from' => '2026-01-01']);

        $head = $this->userWithRole(RoleCode::DepartmentHead);
        $head->assignedDepartments()->attach($ownedDepartment, [
            'valid_from' => '2026-01-01',
            'reason' => 'تكليف',
        ]);

        $this->assertSame(
            [$ownedDepartment->id],
            Department::query()->visibleTo($head)->pluck('id')->all()
        );
        $this->assertSame(
            [$ownedCollege->id],
            College::query()->visibleTo($head)->pluck('id')->all()
        );
    }

    public function test_institution_wide_role_sees_all_colleges_and_departments(): void
    {
        College::factory()->count(2)->create();
        Department::factory()->count(2)->create();

        $president = $this->userWithRole(RoleCode::UniversityPresident);

        $this->assertSame(2, College::query()->visibleTo($president)->count());
        $this->assertSame(2, Department::query()->visibleTo($president)->count());
    }

    public function test_user_without_an_active_role_sees_no_colleges_or_departments(): void
    {
        College::factory()->create();
        Department::factory()->create();
        $user = User::factory()->create();

        $this->assertSame(0, College::query()->visibleTo($user)->count());
        $this->assertSame(0, Department::query()->visibleTo($user)->count());
    }

    public function test_an_expired_college_assignment_does_not_grant_dean_visibility(): void
    {
        $college = College::factory()->create();
        $dean = $this->userWithRole(RoleCode::CollegeDean);
        $dean->assignedColleges()->attach($college, [
            'valid_from' => '2025-01-01',
            'valid_to' => now()->subDay()->toDateString(),
            'reason' => 'تكليف منتهٍ',
        ]);

        $this->assertSame(0, College::query()->visibleTo($dean->fresh())->count());
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
