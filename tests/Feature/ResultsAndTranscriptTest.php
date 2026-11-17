<?php

namespace Tests\Feature;

use App\Actions\AuditGraduation;
use App\Actions\GenerateStudentTranscript;
use App\Actions\PublishResults;
use App\Actions\RecordScore;
use App\Actions\ReviewGradeSubmission;
use App\Actions\SubmitGrades;
use App\Actions\WithdrawPublishedResult;
use App\EnrollmentStatus;
use App\Models\AssessmentComponent;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\GradeApproval;
use App\Models\GradeSubmission;
use App\Models\PublishedResult;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyPlanVersion;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class ResultsAndTranscriptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_results_schema_is_created(): void
    {
        foreach ([
            'assessment_scores', 'grade_submissions', 'grade_approvals',
            'published_results', 'student_transcripts', 'graduation_audits',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('assessment_scores', [
            'course_enrollment_id', 'assessment_component_id', 'raw_score', 'entered_by',
        ]));
        $this->assertTrue(Schema::hasColumns('published_results', [
            'course_section_id', 'course_enrollment_id', 'payload', 'published_at', 'withdrawn_at',
        ]));
    }

    public function test_record_score_is_audited_enforces_component_scope_and_no_invented_rules(): void
    {
        $section = CourseSection::factory()->create();
        $enrollment = CourseEnrollment::factory()->create(['course_section_id' => $section->id]);
        $component = AssessmentComponent::factory()->create(['course_section_id' => $section->id]);
        $foreignComponent = AssessmentComponent::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $record = new RecordScore;

        $score = $record($enrollment, $component, $actor, '85.5');

        $this->assertSame('85.50', $score->raw_score);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'assessment.score_saved', 'actor_id' => $actor->id,
        ]);

        try {
            $record($enrollment, $foreignComponent, $actor, '10');
            $this->fail('عنصر التقييم من شعبة أخرى يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $record($enrollment, $component, $actor, 'غير رقمي');
            $this->fail('الدرجة غير الرقمية يجب أن تُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $score->delete();
            $this->fail('حذف الدرجة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_score_cannot_be_changed_after_approval(): void
    {
        $section = CourseSection::factory()->create();
        $enrollment = CourseEnrollment::factory()->create(['course_section_id' => $section->id]);
        $component = AssessmentComponent::factory()->create(['course_section_id' => $section->id]);
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        (new RecordScore)($enrollment, $component, $actor, '70');
        GradeSubmission::factory()->create([
            'course_section_id' => $section->id,
            'status' => 'approved',
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new RecordScore)($enrollment, $component, $actor, '90');
    }

    public function test_submit_grades_is_unique_per_open_section_and_audited(): void
    {
        $section = CourseSection::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $submit = new SubmitGrades;

        $submission = $submit($section, $actor);

        $this->assertSame('submitted', $submission->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'grades.submitted', 'actor_id' => $actor->id,
        ]);

        try {
            $submit($section, $actor);
            $this->fail('تكرار تقديم مفتوح يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }
    }

    public function test_review_enforces_separation_of_duties_and_approval_is_immutable(): void
    {
        $submitter = $this->userWithRole(RoleCode::AcademicLeadership);
        $approver = $this->userWithRole(RoleCode::DepartmentHead);
        $submission = GradeSubmission::factory()->create(['submitted_by' => $submitter->id]);
        $review = new ReviewGradeSubmission;

        try {
            $review($submission, $submitter, 'approved', 'DEC-1');
            $this->fail('لا يمكن للفاعل نفسه الإدخال والاعتماد.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $approval = $review($submission, $approver, 'approved', 'DEC-1');

        $this->assertSame('approved', $approval->decision);
        $this->assertSame('approved', $submission->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'grades.reviewed', 'actor_id' => $approver->id,
        ]);

        try {
            $approval->update(['decision' => 'rejected']);
            $this->fail('تعديل قرار الاعتماد يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $approval->delete();
            $this->fail('حذف قرار الاعتماد يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_review_rejects_invalid_decision_and_non_approver(): void
    {
        $submission = GradeSubmission::factory()->create();
        $approver = $this->userWithRole(RoleCode::DepartmentHead);

        try {
            (new ReviewGradeSubmission)($submission, $approver, 'bogus', 'DEC');
            $this->fail('القرار غير الصالح يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $outsider = $this->userWithRole(RoleCode::CampusManager);

        try {
            (new ReviewGradeSubmission)($submission, $outsider, 'approved', 'DEC');
            $this->fail('يجب رفض الاعتماد من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertSame(0, GradeApproval::count());
        $this->assertSame('submitted', $submission->fresh()->status);
    }

    public function test_publish_requires_approval_and_creates_immutable_snapshots(): void
    {
        $section = CourseSection::factory()->create();
        CourseEnrollment::factory()->create([
            'course_section_id' => $section->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $publish = new PublishResults;

        try {
            $publish($section, $actor, 'PUB-1');
            $this->fail('لا يمكن النشر قبل الاعتماد.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        GradeSubmission::factory()->create([
            'course_section_id' => $section->id,
            'status' => 'approved',
        ]);

        $results = $publish($section, $actor, 'PUB-1');

        $this->assertCount(1, $results);
        $this->assertSame('PUB-1', $results->first()->decision_reference);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'grades.published', 'actor_id' => $actor->id,
        ]);

        $result = $results->first();

        try {
            $result->update(['payload' => ['tampered' => true]]);
            $this->fail('تعديل النتيجة المنشورة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $result->delete();
            $this->fail('حذف النتيجة المنشورة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_withdraw_publication_requires_reason_and_is_audited(): void
    {
        $result = PublishedResult::factory()->create();
        $actor = $this->userWithRole(RoleCode::AcademicLeadership);
        $withdraw = new WithdrawPublishedResult;

        try {
            $withdraw($result, $actor, '   ');
            $this->fail('سبب السحب مطلوب.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $withdrawn = $withdraw($result, $actor, 'خطأ في النشر');

        $this->assertNotNull($withdrawn->withdrawn_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'grades.publish_withdrawn', 'actor_id' => $actor->id,
        ]);
    }

    public function test_transcript_snapshot_is_immutable_and_audited(): void
    {
        $student = Student::factory()->create();
        $plan = StudyPlanVersion::factory()->create(['status' => 'approved']);
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $generate = new GenerateStudentTranscript;

        $transcript = $generate($student, $plan, $actor, 'TR-1');

        $this->assertSame($student->id, $transcript->student_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.transcript_generated', 'actor_id' => $actor->id,
        ]);

        try {
            $transcript->update(['payload' => ['tampered' => true]]);
            $this->fail('تعديل الكشف يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $transcript->delete();
            $this->fail('حذف الكشف يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_graduation_audit_requires_approved_plan_and_is_immutable(): void
    {
        $student = Student::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $auditGraduation = new AuditGraduation;

        try {
            $auditGraduation(
                $student,
                StudyPlanVersion::factory()->create(['status' => 'draft']),
                $actor,
                'GAUD-1',
            );
            $this->fail('الفحص على خطة غير معتمدة يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $plan = StudyPlanVersion::factory()->create(['status' => 'approved']);
        $audit = $auditGraduation($student, $plan, $actor, 'GAUD-1');

        $this->assertTrue($audit->plan->is($plan));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.graduation_audited', 'actor_id' => $actor->id,
        ]);

        try {
            $audit->update(['payload' => []]);
            $this->fail('تعديل فحص التخرج يجب أن يُرفض.');
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
