<?php

namespace Tests\Feature;

use App\Actions\ApproveAttendanceSession;
use App\Actions\DefineAssessmentComponent;
use App\Actions\OpenAttendanceSession;
use App\Actions\RecordAttendance;
use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\Employee;
use App\Models\Role;
use App\Models\TeachingAssignment;
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

class AttendanceAndAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_attendance_and_assessment_schema_is_created(): void
    {
        foreach (['attendance_sessions', 'attendance_records', 'assessment_components'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('attendance_sessions', [
            'course_section_id', 'session_date', 'status', 'opened_by', 'approved_by', 'approved_at',
        ]));
        $this->assertTrue(Schema::hasColumns('attendance_records', [
            'attendance_session_id', 'course_enrollment_id', 'status', 'excuse_reason', 'recorded_by',
        ]));
        $this->assertTrue(Schema::hasColumns('assessment_components', [
            'course_section_id', 'code', 'name', 'type_code', 'max_score', 'decision_reference',
        ]));
    }

    public function test_open_session_is_unique_per_section_date_and_audited(): void
    {
        $section = CourseSection::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $open = new OpenAttendanceSession;

        $session = $open($section, $actor, Carbon::parse('2040-01-01'));

        $this->assertSame('open', $session->status);
        $this->assertTrue($session->section->is($section));
        $this->assertSame($actor->id, $session->opened_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.session_opened', 'actor_id' => $actor->id,
        ]);

        try {
            $open($section, $actor, Carbon::parse('2040-01-01'));
            $this->fail('تكرار الجلسة في التاريخ نفسه يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        try {
            $session->delete();
            $this->fail('حذف الجلسة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_faculty_can_only_record_on_assigned_section(): void
    {
        $assigned = CourseSection::factory()->create();
        $other = CourseSection::factory()->create();
        $employee = Employee::factory()->create();
        $instructor = User::factory()->create(['person_id' => $employee->person_id]);
        $instructor->roles()->attach(Role::query()->where('code', RoleCode::FacultyMember->value)->firstOrFail());
        TeachingAssignment::factory()->create([
            'course_section_id' => $assigned->id,
            'employee_id' => $employee->id,
            'valid_from' => today()->subDay(),
        ]);

        $session = (new OpenAttendanceSession)($assigned, $instructor->refresh(), Carbon::parse('2040-01-01'));
        $this->assertSame('open', $session->status);

        try {
            (new OpenAttendanceSession)($other, $instructor, Carbon::parse('2040-01-01'));
            $this->fail('لا يمكن للمدرس فتح جلسة لشعبة غير مسندة إليه.');
        } catch (AuthorizationException) {
            // متوقع
        }
    }

    public function test_non_faculty_outsider_cannot_open_a_session(): void
    {
        $section = CourseSection::factory()->create();
        $outsider = $this->userWithRole(RoleCode::CampusManager);

        try {
            (new OpenAttendanceSession)($section, $outsider, Carbon::parse('2040-01-01'));
            $this->fail('يجب رفض فتح جلسة من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertSame(0, AttendanceSession::count());
    }

    public function test_attendance_record_requires_enrollment_in_section_and_excuse_reason(): void
    {
        $section = CourseSection::factory()->create();
        $session = AttendanceSession::factory()->create(['course_section_id' => $section->id]);
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $enrollment = CourseEnrollment::factory()->create(['course_section_id' => $section->id]);
        $foreignEnrollment = CourseEnrollment::factory()->create();

        try {
            (new RecordAttendance)($session, $foreignEnrollment, AttendanceStatus::Present, $actor);
            $this->fail('لا يمكن تسجيل حضور لطالب غير مسجل في الشعبة.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            (new RecordAttendance)($session, $enrollment, AttendanceStatus::Excused, $actor);
            $this->fail('سبب العذر مطلوب.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $record = (new RecordAttendance)(
            $session, $enrollment, AttendanceStatus::Present, $actor,
        );
        $this->assertSame(AttendanceStatus::Present, $record->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.record_saved', 'actor_id' => $actor->id,
        ]);
    }

    public function test_attendance_record_is_upserted_with_change_logged(): void
    {
        $section = CourseSection::factory()->create();
        $session = AttendanceSession::factory()->create(['course_section_id' => $section->id]);
        $enrollment = CourseEnrollment::factory()->create(['course_section_id' => $section->id]);
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $recordAttendance = new RecordAttendance;

        $recordAttendance($session, $enrollment, AttendanceStatus::Present, $actor);
        $updated = $recordAttendance($session, $enrollment, AttendanceStatus::Absent, $actor);

        $this->assertSame(1, AttendanceRecord::count());
        $this->assertSame(AttendanceStatus::Absent, $updated->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.record_saved',
            'actor_id' => $actor->id,
        ]);

        try {
            $updated->delete();
            $this->fail('حذف سجل الحضور يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_approved_session_is_locked_and_approval_audited(): void
    {
        $section = CourseSection::factory()->create();
        $session = AttendanceSession::factory()->create(['course_section_id' => $section->id]);
        $enrollment = CourseEnrollment::factory()->create(['course_section_id' => $section->id]);
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);

        $approved = (new ApproveAttendanceSession)($session, $actor);

        $this->assertSame('approved', $approved->status);
        $this->assertNotNull($approved->approved_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.session_approved', 'actor_id' => $actor->id,
        ]);

        try {
            (new RecordAttendance)($session, $enrollment, AttendanceStatus::Present, $actor);
            $this->fail('لا يمكن تعديل سجلات جلسة معتمدة.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            (new ApproveAttendanceSession)($session, $actor);
            $this->fail('اعتماد الجلسة مرتين يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }
    }

    public function test_assessment_component_code_is_unique_with_no_invented_max_score(): void
    {
        $section = CourseSection::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $define = new DefineAssessmentComponent;

        $component = $define($section, $actor, 'AC-1', 'اختبار');

        $this->assertNull($component->max_score);
        $this->assertTrue($component->section->is($section));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'assessment.component_defined', 'actor_id' => $actor->id,
        ]);

        try {
            $define($section, $actor, 'AC-1', 'مكرر');
            $this->fail('تكرار رمز عنصر التقييم يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        try {
            $component->delete();
            $this->fail('حذف عنصر التقييم يجب أن يُرفض.');
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
