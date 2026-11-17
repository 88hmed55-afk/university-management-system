<?php

namespace Tests\Feature;

use App\Actions\ApproveStudyPlanVersion;
use App\Actions\AssignStudentPlan;
use App\Actions\DefineCourse;
use App\Models\Course;
use App\Models\CoursePrerequisite;
use App\Models\CourseVersion;
use App\Models\Department;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyPlanCourse;
use App\Models\StudyPlanVersion;
use App\Models\User;
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

class CourseCatalogAndStudyPlansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_catalog_schema_is_created(): void
    {
        foreach ([
            'courses', 'course_versions', 'study_plan_versions',
            'study_plan_courses', 'course_prerequisites', 'student_plan_assignments',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('courses', [
            'code', 'title', 'description', 'department_id', 'status',
        ]));
        $this->assertTrue(Schema::hasColumns('study_plan_versions', [
            'academic_program_id', 'version_no', 'approved_at', 'effective_from', 'status',
        ]));
        $this->assertTrue(Schema::hasColumns('course_prerequisites', [
            'course_id', 'prerequisite_course_id', 'condition_type_code',
        ]));
    }

    public function test_course_code_is_unique_and_courses_are_not_deletable(): void
    {
        $course = Course::factory()->create(['code' => 'CRS-TEST']);

        try {
            Course::factory()->create(['code' => 'CRS-TEST']);
            $this->fail('رمز المقرر المكرر يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        try {
            $course->delete();
            $this->fail('حذف المقرر يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->assertNotNull($course->fresh());
    }

    public function test_define_course_is_authorized_and_audited(): void
    {
        $department = Department::factory()->create();
        $actor = $this->userWithRole(RoleCode::DepartmentHead);

        $course = (new DefineCourse)($actor, 'CRS-NEW', 'مقرر جديد', $department);

        $this->assertTrue($course->department->is($department));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic.course_defined', 'actor_id' => $actor->id,
        ]);

        $outsider = $this->userWithRole(RoleCode::CampusManager);

        try {
            (new DefineCourse)($outsider, 'CRS-NO', 'مقرر', $department);
            $this->fail('يجب رفض تعريف مقرر من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }
    }

    public function test_course_version_is_unique_and_approved_one_is_immutable(): void
    {
        $course = Course::factory()->create();
        CourseVersion::factory()->create(['course_id' => $course->id, 'version_no' => 1]);

        try {
            CourseVersion::factory()->create(['course_id' => $course->id, 'version_no' => 1]);
            $this->fail('رقم إصدار مكرر يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        $approved = CourseVersion::factory()->create([
            'course_id' => $course->id,
            'version_no' => 2,
            'status' => 'approved',
        ]);

        $this->assertSame(1, CourseVersion::query()->approved()->count());

        try {
            $approved->update(['version_no' => 3]);
            $this->fail('تعديل نسخة معتمدة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_plan_approval_is_authorized_documented_and_immutable(): void
    {
        $version = StudyPlanVersion::factory()->create();
        $actor = $this->userWithRole(RoleCode::AcademicLeadership);

        try {
            (new ApproveStudyPlanVersion)($version, $actor, '   ');
            $this->fail('مرجع الاعتماد مطلوب.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $approved = (new ApproveStudyPlanVersion)(
            $version, $actor, 'PLAN-APPROVAL-1', Carbon::parse('2040-01-01'),
        );

        $this->assertSame('approved', $approved->status);
        $this->assertNotNull($approved->approved_at);
        $this->assertSame($actor->id, $approved->approved_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic.study_plan_approved', 'actor_id' => $actor->id,
        ]);

        try {
            $approved->update(['version_no' => 9]);
            $this->fail('تعديل خطة معتمدة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $outsider = $this->userWithRole(RoleCode::HrDirector);

        try {
            (new ApproveStudyPlanVersion)(
                StudyPlanVersion::factory()->create(), $outsider, 'PLAN-NO',
            );
            $this->fail('يجب رفض اعتماد من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }
    }

    public function test_plan_courses_are_unique_and_hold_no_invented_credit_value(): void
    {
        $plan = StudyPlanVersion::factory()->create();
        $course = Course::factory()->create();
        $row = StudyPlanCourse::factory()->create([
            'study_plan_version_id' => $plan->id, 'course_id' => $course->id,
        ]);

        $this->assertNull($row->credits);
        $this->assertTrue($row->course->is($course));
        $this->assertTrue($row->plan->is($plan));

        try {
            StudyPlanCourse::factory()->create([
                'study_plan_version_id' => $plan->id, 'course_id' => $course->id,
            ]);
            $this->fail('تكرار المقرر في الخطة يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        try {
            $row->delete();
            $this->fail('حذف مقرر الخطة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_prerequisites_reject_self_and_cycles(): void
    {
        $a = Course::factory()->create();
        $b = Course::factory()->create();

        try {
            CoursePrerequisite::factory()->create([
                'course_id' => $a->id, 'prerequisite_course_id' => $a->id,
            ]);
            $this->fail('المقرر لا يكون متطلباً لنفسه.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $valid = CoursePrerequisite::factory()->create([
            'course_id' => $a->id, 'prerequisite_course_id' => $b->id,
        ]);

        $this->assertTrue($valid->prerequisiteCourse->is($b));
        $this->assertSame(1, CoursePrerequisite::count());

        try {
            CoursePrerequisite::factory()->create([
                'course_id' => $b->id, 'prerequisite_course_id' => $a->id,
            ]);
            $this->fail('دورة المتطلبات يجب أن تُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }
    }

    public function test_student_plan_assignment_requires_approved_plan_and_no_overlap(): void
    {
        $student = Student::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $draft = StudyPlanVersion::factory()->create();

        try {
            (new AssignStudentPlan)(
                $student, $draft, $actor, 'PLAN-NO', Carbon::parse('2040-01-01'),
            );
            $this->fail('لا يمكن ربط خطة غير معتمدة.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $approved = StudyPlanVersion::factory()->create(['status' => 'approved']);
        $assignment = (new AssignStudentPlan)(
            $student, $approved, $actor, 'PLAN-OK', Carbon::parse('2040-01-01'),
        );

        $this->assertTrue($assignment->student->is($student));
        $this->assertTrue($assignment->plan->is($approved));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.plan_assigned', 'actor_id' => $actor->id,
        ]);

        try {
            (new AssignStudentPlan)(
                $student, $approved, $actor, 'PLAN-OVERLAP', Carbon::parse('2040-03-01'),
            );
            $this->fail('ربط خطتين نشطتين متداخلتين يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $assignment->delete();
            $this->fail('حذف ربط الخطة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
