<?php

namespace Tests\Feature;

use App\Actions\DropCourseEnrollment;
use App\Actions\EnrollStudentInSection;
use App\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseOffering;
use App\Models\CoursePrerequisite;
use App\Models\CourseSection;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class CourseRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_registration_schema_is_created(): void
    {
        $this->assertTrue(Schema::hasTable('course_enrollments'));
        $this->assertTrue(Schema::hasColumns('course_enrollments', [
            'student_id', 'course_section_id', 'status', 'source',
            'idempotency_key', 'decision_reference', 'enrolled_at', 'dropped_at',
        ]));
    }

    public function test_active_duplicate_in_same_offering_is_rejected_and_rows_are_not_deletable(): void
    {
        $section = CourseSection::factory()->create();
        $student = Student::factory()->create();
        $enrollment = CourseEnrollment::factory()->create([
            'student_id' => $student->id,
            'course_section_id' => $section->id,
        ]);

        try {
            CourseEnrollment::factory()->create([
                'student_id' => $student->id,
                'course_section_id' => CourseSection::factory()->create([
                    'course_offering_id' => $section->course_offering_id,
                ])->id,
            ]);
            $this->fail('تكرار التسجيل في المقرر والفصل يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $enrollment->delete();
            $this->fail('حذف التسجيل يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->assertNotNull($enrollment->fresh());
    }

    public function test_admin_enrollment_is_audited_and_idempotent(): void
    {
        $section = CourseSection::factory()->create();
        $student = Student::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $enroll = new EnrollStudentInSection;

        $first = $enroll($student, $section, $actor, 'ENROLL-KEY-1', 'DEC-1');
        $second = $enroll($student, $section, $actor, 'ENROLL-KEY-1', 'DEC-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('admin', $first->source);
        $this->assertSame(EnrollmentStatus::Enrolled, $first->status);
        $this->assertSame(1, CourseEnrollment::count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.course_enrolled', 'actor_id' => $actor->id,
        ]);
    }

    public function test_capacity_is_enforced_without_invented_default(): void
    {
        $section = CourseSection::factory()->create(['capacity' => 1]);
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $enroll = new EnrollStudentInSection;

        $enroll(Student::factory()->create(), $section, $actor);

        $this->expectException(InvalidArgumentException::class);

        $enroll(Student::factory()->create(), $section, $actor);
    }

    public function test_prerequisite_must_be_completed(): void
    {
        $prerequisiteCourse = Course::factory()->create();
        $targetCourse = Course::factory()->create();
        CoursePrerequisite::factory()->create([
            'course_id' => $targetCourse->id,
            'prerequisite_course_id' => $prerequisiteCourse->id,
        ]);
        $targetSection = CourseSection::factory()->create([
            'course_offering_id' => CourseOffering::factory()->create([
                'course_id' => $targetCourse->id,
            ])->id,
        ]);
        $student = Student::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $enroll = new EnrollStudentInSection;

        try {
            $enroll($student, $targetSection, $actor);
            $this->fail('يجب رفض التسجيل قبل استكمال المتطلب.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $prerequisiteSection = CourseSection::factory()->create([
            'course_offering_id' => CourseOffering::factory()->create([
                'course_id' => $prerequisiteCourse->id,
            ])->id,
        ]);
        CourseEnrollment::factory()->create([
            'student_id' => $student->id,
            'course_section_id' => $prerequisiteSection->id,
            'status' => EnrollmentStatus::Completed,
        ]);

        $enrollment = $enroll($student, $targetSection, $actor);
        $this->assertTrue($enrollment->section->is($targetSection));
    }

    public function test_schedule_conflict_is_rejected(): void
    {
        $firstSection = CourseSection::factory()->create();
        $secondSection = CourseSection::factory()->create();
        $firstSection->meetings()->create([
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:30',
            'valid_from' => '2040-01-01',
        ]);
        $secondSection->meetings()->create([
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:30',
            'valid_from' => '2040-01-01',
        ]);

        $student = Student::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $enroll = new EnrollStudentInSection;
        $enroll($student, $firstSection, $actor);

        $this->expectException(InvalidArgumentException::class);

        $enroll($student, $secondSection, $actor);
    }

    public function test_self_service_is_limited_to_the_student_himself(): void
    {
        $section = CourseSection::factory()->create();
        $student = Student::factory()->create();
        $self = User::factory()->create([
            'person_id' => $student->person_id,
        ]);
        $self->roles()->attach(Role::query()->where('code', RoleCode::Student->value)->firstOrFail());

        $enrollment = (new EnrollStudentInSection)($student, $section, $self->refresh());
        $this->assertSame('self', $enrollment->source);

        $anotherStudent = Student::factory()->create();

        try {
            (new EnrollStudentInSection)(
                $anotherStudent,
                CourseSection::factory()->create(),
                $self,
            );
            $this->fail('لا يمكن للطالب التسجيل لشخص آخر.');
        } catch (AuthorizationException) {
            // متوقع
        }
    }

    public function test_non_registration_actor_cannot_enroll(): void
    {
        $section = CourseSection::factory()->create();
        $actor = $this->userWithRole(RoleCode::CampusManager);

        try {
            (new EnrollStudentInSection)(Student::factory()->create(), $section, $actor);
            $this->fail('يجب رفض التسجيل من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertSame(0, CourseEnrollment::count());
    }

    public function test_drop_closes_enrollment_with_reason_and_audit(): void
    {
        $enrollment = CourseEnrollment::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);

        try {
            (new DropCourseEnrollment)($enrollment, $actor, '   ');
            $this->fail('سبب الانسحاب مطلوب.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $dropped = (new DropCourseEnrollment)($enrollment, $actor, 'انسحاب');

        $this->assertSame(EnrollmentStatus::Dropped, $dropped->status);
        $this->assertNotNull($dropped->dropped_at);
        $this->assertNotNull($dropped->fresh());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.course_dropped', 'actor_id' => $actor->id,
        ]);
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
