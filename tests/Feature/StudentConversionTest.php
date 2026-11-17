<?php

namespace Tests\Feature;

use App\Actions\ChangeStudentStatus;
use App\Actions\ConvertApplicantToStudent;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\AdmissionDecision;
use App\Models\ApplicantEvent;
use App\Models\Application;
use App\Models\College;
use App\Models\Document;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\RoleCode;
use App\StudentStatus;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class StudentConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_student_schema_is_created(): void
    {
        foreach (['students', 'student_program_enrollments', 'student_status_history', 'student_documents'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('students', [
            'person_id', 'student_no', 'source_application_id', 'status', 'archived_at',
        ]));
        $this->assertTrue(Schema::hasColumns('student_status_history', [
            'student_id', 'from_status', 'to_status', 'reason', 'effective_date', 'decision_reference',
        ]));
    }

    public function test_student_number_is_unique_and_students_are_not_deletable(): void
    {
        $student = Student::factory()->create(['student_no' => 'STU-0001']);

        try {
            Student::factory()->create(['student_no' => 'STU-0001']);
            $this->fail('رقم الطالب المكرر يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        try {
            $student->delete();
            $this->fail('حذف سجل الطالب يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->assertNotNull($student->fresh());
    }

    public function test_approved_application_converts_idempotently(): void
    {
        $application = Application::factory()->create();
        AdmissionDecision::factory()->create([
            'application_id' => $application->id,
            'decision' => 'approved',
        ]);
        $program = AcademicProgram::factory()->create();
        $year = AcademicYear::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);

        $convert = new ConvertApplicantToStudent;

        $student = $convert($application, 'STU-CONV-1', $program, $year, $actor);
        $again = $convert($application, 'STU-CONV-1', $program, $year, $actor);

        $this->assertSame($student->id, $again->id);
        $this->assertSame(1, Student::count());
        $this->assertSame(1, $student->enrollments()->count());
        $this->assertSame(1, $student->statusHistory()->count());
        $this->assertTrue($student->person->is($application->person));
        $this->assertTrue($student->sourceApplication->is($application));
        $this->assertTrue($student->enrollments()->first()->program->is($program));
        $this->assertTrue($student->enrollments()->first()->year->is($year));
        $this->assertDatabaseHas('applicant_events', [
            'application_id' => $application->id,
            'event_type' => 'converted_to_student',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.converted_from_application',
            'actor_id' => $actor->id,
        ]);
    }

    public function test_unapproved_application_cannot_be_converted(): void
    {
        $application = Application::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);

        $this->expectException(InvalidArgumentException::class);

        (new ConvertApplicantToStudent)(
            $application,
            'STU-NO',
            AcademicProgram::factory()->create(),
            AcademicYear::factory()->create(),
            $actor,
        );
    }

    public function test_person_with_existing_student_record_cannot_be_converted_again(): void
    {
        $application = Application::factory()->create();
        AdmissionDecision::factory()->create([
            'application_id' => $application->id,
            'decision' => 'approved',
        ]);
        Student::factory()->create(['person_id' => $application->person_id]);
        $actor = $this->userWithRole(RoleCode::AdmissionsRegistrationDirector);

        $this->expectException(InvalidArgumentException::class);

        (new ConvertApplicantToStudent)(
            $application,
            'STU-DUP',
            AcademicProgram::factory()->create(),
            AcademicYear::factory()->create(),
            $actor,
        );
    }

    public function test_non_registration_actor_cannot_convert(): void
    {
        $application = Application::factory()->create();
        AdmissionDecision::factory()->create(['application_id' => $application->id]);
        $actor = $this->userWithRole(RoleCode::CampusManager);

        try {
            (new ConvertApplicantToStudent)(
                $application,
                'STU-NO',
                AcademicProgram::factory()->create(),
                AcademicYear::factory()->create(),
                $actor,
            );
            $this->fail('يجب رفض تحويل من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertSame(0, Student::count());
        $this->assertSame(0, ApplicantEvent::count());
    }

    public function test_status_change_is_audited_with_reason_and_is_immutable_in_history(): void
    {
        $student = Student::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);

        $history = (new ChangeStudentStatus)(
            $student,
            StudentStatus::Deferred,
            $actor,
            'تأجيل دراسي',
            'DEC-DEFER-1',
        );

        $this->assertSame(StudentStatus::Active, $history->from_status);
        $this->assertSame(StudentStatus::Deferred, $history->to_status);
        $this->assertSame(StudentStatus::Deferred, $student->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.status_changed',
            'actor_id' => $actor->id,
        ]);

        App::setLocale('ar');
        $this->assertSame('مؤجل', StudentStatus::Deferred->label());
        App::setLocale('en');
        $this->assertSame('Deferred', StudentStatus::Deferred->label());

        try {
            $history->update(['reason' => 'تعديل']);
            $this->fail('تعديل سجل الحالة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $history->delete();
            $this->fail('حذف سجل الحالة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_status_change_requires_reason_and_reference(): void
    {
        $student = Student::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);

        try {
            (new ChangeStudentStatus)($student, StudentStatus::Withdrawn, $actor, '', 'DEC');
            $this->fail('السبب مطلوب.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $this->expectException(InvalidArgumentException::class);

        (new ChangeStudentStatus)($student, StudentStatus::Withdrawn, $actor, 'سبب', '   ');
    }

    public function test_student_document_links_metadata_to_document_without_deletion(): void
    {
        $student = Student::factory()->create();
        $document = Document::factory()->create();

        $link = StudentDocument::factory()->create([
            'student_id' => $student->id,
            'document_id' => $document->id,
        ]);

        $this->assertTrue($link->student->is($student));
        $this->assertTrue($link->document->is($document));

        try {
            $link->delete();
            $this->fail('حذف ربط الوثيقة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->assertNotNull($link->fresh());
    }

    public function test_student_visibility_is_scoped_by_primary_college_for_dean(): void
    {
        $college = College::factory()->create();
        $program = AcademicProgram::factory()->create(['primary_college_id' => $college->id]);
        $year = AcademicYear::factory()->create();
        $inCollege = Student::factory()->create();
        $inCollege->enrollments()->create([
            'academic_program_id' => $program->id,
            'academic_year_id' => $year->id,
        ]);
        $outside = Student::factory()->create();

        $dean = $this->userWithRole(RoleCode::CollegeDean);
        $dean->assignedColleges()->attach($college, ['valid_from' => today()]);
        $registrar = $this->userWithRole(RoleCode::RegistrationOfficer);
        $noRole = User::factory()->create();

        $this->assertSame([$inCollege->id], Student::query()->visibleTo($dean->refresh())->pluck('id')->all());
        $this->assertSame(2, Student::query()->visibleTo($registrar)->count());
        $this->assertSame(0, Student::query()->visibleTo($noRole)->count());
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
