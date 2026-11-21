<?php

namespace Tests\Feature;

use App\Actions\AddBibliographicRecord;
use App\Actions\AddItemCopy;
use App\Actions\ApproveResearchProposal;
use App\Actions\ApproveSupervisorAssignment;
use App\Actions\ArchiveLibraryBranch;
use App\Actions\CancelReservation;
use App\Actions\ChangeSupervisorAssignment;
use App\Actions\CompleteDefenseSession;
use App\Actions\DefineLibraryBranch;
use App\Actions\EvaluateResearchMilestone;
use App\Actions\NominateSupervisor;
use App\Actions\OpenPostgraduateRecord;
use App\Actions\RecordCirculation;
use App\Actions\RecordDefenseMinutes;
use App\Actions\ReserveBibliographicRecord;
use App\Actions\ScheduleDefenseSession;
use App\Actions\SubmitResearchMilestone;
use App\Actions\SubmitResearchProposal;
use App\Models\AcademicProgram;
use App\Models\AuditLog;
use App\Models\BibliographicRecord;
use App\Models\Campus;
use App\Models\CirculationTransaction;
use App\Models\DefenseSession;
use App\Models\Document;
use App\Models\Employee;
use App\Models\ItemCopy;
use App\Models\LibraryBranch;
use App\Models\Person;
use App\Models\PostgraduateRecord;
use App\Models\ResearchProposal;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class LibraryAndPostgraduateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_library_and_postgraduate_schema_is_created(): void
    {
        foreach ([
            'library_branches', 'bibliographic_records', 'item_copies',
            'circulation_transactions', 'reservations', 'postgraduate_records',
            'supervisor_assignments', 'research_proposals', 'research_milestones',
            'defense_sessions',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('circulation_transactions', [
            'item_copy_id', 'beneficiary_type', 'beneficiary_id', 'transaction_type',
            'original_transaction_id', 'due_at', 'occurred_at', 'acted_by',
        ]));
        $this->assertTrue(Schema::hasColumns('supervisor_assignments', [
            'employee_id', 'role_code', 'valid_from', 'valid_to', 'nominated_by', 'approved_by',
        ]));
        $this->assertTrue(Schema::hasColumns('defense_sessions', [
            'minutes_document_id', 'recommendation', 'requirements_reference', 'status',
        ]));
    }

    public function test_branches_catalog_and_copies_are_scoped_and_immutable(): void
    {
        $admin = $this->userWithRole(RoleCode::SystemAdmin);
        $president = $this->userWithRole(RoleCode::UniversityPresident);
        $campusManager = $this->userWithRole(RoleCode::CampusManager);
        $firstCampus = Campus::factory()->create();
        $secondCampus = Campus::factory()->create();
        $campusManager->campuses()->attach($firstCampus);

        $define = new DefineLibraryBranch;

        try {
            $define($campusManager, 'LIB-X', 'مكتبة', $firstCampus);
            $this->fail('تعريف فرع من غير أمين مكتبة يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $first = $define($admin, 'LIB-1', 'مكتبة الأولى', $firstCampus);
        $second = $define($admin, 'LIB-2', 'مكتبة الثانية', $secondCampus);

        $this->assertSame(
            [$first->id, $second->id],
            LibraryBranch::query()->visibleTo($president)->orderBy('id')->pluck('id')->all(),
        );
        $this->assertSame(
            [$first->id],
            LibraryBranch::query()->visibleTo($campusManager->refresh())->pluck('id')->all(),
        );

        $archived = (new ArchiveLibraryBranch)($second, $admin, 'دمج المكتبتين', 'DEC-ARCH');
        $this->assertSame('archived', $archived->status);
        $this->assertNotNull($archived->archived_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'library.branch_archived', 'reason' => 'دمج المكتبتين']);

        try {
            (new AddBibliographicRecord)($admin, 'BIB-X', 'عنوان', $archived);
            $this->fail('إضافة سجل لفرع مؤرشف يجب أن تُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $record = (new AddBibliographicRecord)(
            $admin, 'BIB-1', 'كتاب اختبار', $first, 'مؤلف', 'ABC-123', null, 'ar', null, 2026,
        );
        $this->assertSame('active', $record->status);

        $copy = (new AddItemCopy)($admin, 'CPY-1', $record, $first, 'LOC-1');
        $this->assertSame('available', $copy->status);

        try {
            (new AddItemCopy)($admin, 'CPY-2', $record, $second);
            $this->fail('نسخة السجل يجب أن تُضاف في فرع السجل نفسه.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $record->update(['record_no' => 'BIB-9']);
            $this->fail('تعديل رقم سجل الفهرس يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $copy->delete();
            $this->fail('حذف نسخة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $first->delete();
            $this->fail('حذف فرع يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_circulation_requires_a_decision_referenced_due_date_and_keeps_history(): void
    {
        $librarian = $this->userWithRole(RoleCode::Librarian);
        $outsider = $this->userWithRole(RoleCode::FacilitiesOfficer);
        $student = Student::factory()->create();
        $copy = ItemCopy::factory()->create();
        $record = new RecordCirculation;

        try {
            $record($outsider, $copy, 'checkout', Student::class, $student->id, 'CIR-X', null, '2026-12-01', 'D-1');
            $this->fail('تداول من غير أمين مكتبة يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        try {
            $record($librarian, $copy, 'checkout', Student::class, $student->id, 'CIR-X');
            $this->fail('إعارة بلا تاريخ استحقاق ومرجع قرار يجب أن تُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $checkout = $record(
            $librarian, $copy, 'checkout', Student::class, $student->id,
            'CIR-1', null, '2026-12-01 10:00:00', 'LOAN-REG-1',
        );
        $this->assertSame('checkout', $checkout->transaction_type);
        $this->assertSame('on_loan', $copy->fresh()->status);

        try {
            $checkout->update(['transaction_type' => 'return']);
            $this->fail('تعديل حركة تداول يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $record($librarian, $copy, 'return', Student::class, $student->id, 'CIR-X');
            $this->fail('إرجاع بلا إشارة للإعارة الأصلية يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $return = $record(
            $librarian, $copy, 'return', Student::class, $student->id, 'CIR-2', $checkout->id,
        );
        $this->assertSame($checkout->id, $return->original_transaction_id);
        $this->assertSame('available', $copy->fresh()->status);

        $secondCheckout = $record(
            $librarian, $copy, 'checkout', Student::class, $student->id,
            'CIR-3', null, '2026-12-20 10:00:00', 'LOAN-REG-2',
        );
        $renewal = $record(
            $librarian, $copy, 'renewal', Student::class, $student->id,
            'CIR-4', $secondCheckout->id,
            '2027-01-20 10:00:00', 'RENEW-REG-1',
        );
        $this->assertSame('renewal', $renewal->transaction_type);
        $this->assertSame('on_loan', $copy->fresh()->status);

        $damage = $record(
            $librarian, $copy, 'damage', Student::class, $student->id,
            'CIR-5', $secondCheckout->id, '2027-01-20 10:00:00', 'DAMAGE-REG-1', 'تمزق في الغلاف',
        );
        $this->assertSame('damaged', $copy->fresh()->status);
        $this->assertSame(5, CirculationTransaction::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'library.circulation_recorded', 'actor_id' => $librarian->id]);

        try {
            $damage->delete();
            $this->fail('حذف حركة تداول يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_reservations_are_self_only_idempotent_and_documented(): void
    {
        $person = Person::factory()->create();
        $researcher = User::factory()->create(['person_id' => $person->id]);
        $this->userWithRole(RoleCode::Student, $researcher);
        $student = Student::factory()->create(['person_id' => $person->id]);
        $otherStudent = Student::factory()->create();
        $record = BibliographicRecord::factory()->create();
        $reserve = new ReserveBibliographicRecord;

        $reservation = $reserve($researcher, $record, 'RES-1', Student::class, $student->id);
        $this->assertSame('requested', $reservation->status);

        $again = $reserve(
            $researcher, $record, 'RES-2', Student::class, $student->id, null, null, 'IDEM-RES',
        );
        $third = $reserve(
            $researcher, $record, 'RES-3', Student::class, $student->id, null, null, 'IDEM-RES',
        );
        $this->assertSame($again->id, $third->id);
        $this->assertSame(2, Reservation::count());

        try {
            $reserve($researcher, $record, 'RES-X', Student::class, $otherStudent->id);
            $this->fail('حجز لمستفيد آخر يجب أن يُرفض (SCR-082).');
        } catch (AuthorizationException) {
            // متوقع
        }

        try {
            $reserve($researcher, $record, 'RES-X', Student::class, $student->id, now()->addMonth()->toDateTimeString());
            $this->fail('تاريخ انتهاء حجز بلا مرجع قرار يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            (new CancelReservation)($reservation, $researcher, '');
            $this->fail('إلغاء حجز بلا سبب يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $cancelled = (new CancelReservation)($reservation, $researcher, 'لم يعد مطلوباً');
        $this->assertSame('cancelled', $cancelled->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'library.reservation_cancelled', 'reason' => 'لم يعد مطلوباً']);

        try {
            $reservation->delete();
            $this->fail('حذف حجز يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_postgraduate_files_and_supervisions_require_decisions_and_separation(): void
    {
        $coordinator = $this->userWithRole(RoleCode::GraduateStudiesCoordinator);
        $approver = $this->userWithRole(RoleCode::AcademicLeadership);
        $student = Student::factory()->create();
        $program = AcademicProgram::factory()->create();
        $open = new OpenPostgraduateRecord;

        try {
            $open($coordinator, 'PG-X', $student, $program, 'masters');
            $this->fail('فتح ملف بلا مرجع قبول وقرار يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $record = $open(
            $coordinator, 'PG-1', $student, $program, 'masters', 'ADM-REF-1', 'DEC-OPEN',
        );
        $this->assertSame('active', $record->status);
        $this->assertSame($program->id, $record->program_id);

        try {
            $open($coordinator, 'PG-2', $student, $program, 'masters', 'ADM-REF-2', 'DEC-OPEN-2');
            $this->fail('تكرار ملف للطالب والبرنامج يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $employee = Employee::factory()->create();
        $nominate = new NominateSupervisor;
        $validFrom = '2026-09-01';

        try {
            $nominate($coordinator, $record, $employee, 'supervisor', $validFrom, null, null, 'DEC-NOM');
            $this->fail('ترشيح بلا مرجع أهلية يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $assignment = $nominate(
            $coordinator, $record, $employee, 'supervisor', $validFrom, null, null,
            'DEC-NOM', 'ELIG-1',
        );
        $this->assertSame('nominated', $assignment->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'postgraduate.supervisor_nominated']);

        try {
            (new ApproveSupervisorAssignment)($assignment, $coordinator, 'DEC-APP');
            $this->fail('اعتماد المشرف من مترشحه يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $approved = (new ApproveSupervisorAssignment)($assignment, $approver, 'DEC-APP');
        $this->assertSame('approved', $approved->status);

        $newSupervisor = Employee::factory()->create();
        $changed = (new ChangeSupervisorAssignment)(
            $approved, $coordinator, $newSupervisor, 'supervisor', '2026-10-01',
            'تغيير بقرار', 'DEC-CHANGE', 'ELIG-2',
        );
        $this->assertSame('nominated', $changed->status);
        $this->assertSame('2026-09-30', $approved->fresh()->valid_to->toDateString());
        $this->assertSame('closed', $approved->fresh()->status);
        $this->assertSame(2, SupervisorAssignment::count());

        try {
            $record->delete();
            $this->fail('حذف ملف دراسات عليا يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $record->update(['student_id' => Student::factory()->create()->id]);
            $this->fail('تعديل هوية ملف الدراسات العليا يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_proposal_versions_are_retained_and_approval_requires_separation(): void
    {
        $person = Person::factory()->create();
        $researcher = User::factory()->create(['person_id' => $person->id]);
        $this->userWithRole(RoleCode::Student, $researcher);
        $student = Student::factory()->create(['person_id' => $person->id]);
        $coordinator = $this->userWithRole(RoleCode::GraduateStudiesCoordinator);
        $approver = $this->userWithRole(RoleCode::AcademicLeadership);
        $record = PostgraduateRecord::factory()->create(['student_id' => $student->id]);
        $submit = new SubmitResearchProposal;

        $proposal = $submit($researcher, $record, 'PROP-1', 1, 'عنوان', 'ملخص');
        $this->assertSame('submitted', $proposal->status);
        $this->assertSame($researcher->id, $proposal->submitted_by);

        try {
            $submit($researcher, $record, 'PROP-2', 1, 'عنوان آخر', 'ملخص');
            $this->fail('تكرار إصدار المقترح يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $submit($researcher, PostgraduateRecord::factory()->create(), 'PROP-X', 1, 'عنوان', 'ملخص');
            $this->fail('رفع مقترح لملف بحث آخر يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $second = $submit($coordinator, $record, 'PROP-3', 2, 'عنوان منقح', 'ملخص منقح');
        $this->assertSame(2, $second->version_no);
        $this->assertSame(2, ResearchProposal::count());

        try {
            (new ApproveResearchProposal)($second, $coordinator, 'DEC-APP');
            $this->fail('اعتماد المقترح من مقدمه يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $approved = (new ApproveResearchProposal)($second, $approver, 'DEC-APP');
        $this->assertSame('approved', $approved->status);

        try {
            $proposal->delete();
            $this->fail('حذف نسخة مقترح يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $approved->update(['title' => 'تعديل']);
            $this->fail('تعديل نسخة معتمدة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_milestone_evaluation_requires_separation_and_a_decision_reference(): void
    {
        $coordinator = $this->userWithRole(RoleCode::GraduateStudiesCoordinator);
        $supervisor = $this->userWithRole(RoleCode::FacultyMember);
        $record = PostgraduateRecord::factory()->create();
        $submit = new SubmitResearchMilestone;

        try {
            $submit($coordinator, $record, 'MIL-X', 'مرحلة', 'تقرير', now()->addMonth()->toDateTimeString());
            $this->fail('تاريخ مستحق بلا مرجع قرار يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $milestone = $submit($coordinator, $record, 'MIL-1', 'مرحلة', 'تقرير');
        $this->assertSame('submitted', $milestone->status);

        $evaluate = new EvaluateResearchMilestone;

        try {
            $evaluate($milestone, $coordinator, 'مقبول', 'DEC-EVAL');
            $this->fail('تقييم المرحلة من مقدمها يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $evaluated = $evaluate($milestone, $supervisor, 'مقبول مع ملاحظات', 'DEC-EVAL');
        $this->assertSame('evaluated', $evaluated->status);
        $this->assertSame($supervisor->id, $evaluated->evaluated_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'postgraduate.milestone_evaluated']);

        try {
            $milestone->delete();
            $this->fail('حذف مرحلة بحث يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_defense_requires_requirements_reference_and_is_not_a_graduation_decision(): void
    {
        $coordinator = $this->userWithRole(RoleCode::GraduateStudiesCoordinator);
        $approver = $this->userWithRole(RoleCode::AcademicLeadership);
        $record = PostgraduateRecord::factory()->create();
        $proposal = ResearchProposal::factory()->create([
            'postgraduate_record_id' => $record->id,
            'status' => 'approved',
        ]);
        $schedule = new ScheduleDefenseSession;

        try {
            $schedule($coordinator, $record, 'DEF-X', now()->addWeek()->toDateTimeString(), '');
            $this->fail('جدولة مناقشة بلا مرجع متطلبات يجب أن تُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $session = $schedule(
            $coordinator, $record, 'DEF-1', now()->addWeek()->toDateTimeString(),
            'DEF-REQ-APPROVED', 'COM-1', 'قاعة أ', $proposal, 'DEC-SCHEDULE',
        );
        $this->assertSame('scheduled', $session->status);

        try {
            (new CompleteDefenseSession)($session, $approver, 'DEC-DONE', 'EVID-1');
            $this->fail('إتمام مناقشة بلا محضر يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $minutes = Document::factory()->create();
        $withMinutes = (new RecordDefenseMinutes)(
            $session, $coordinator, $minutes, 'توصية اللجنة', 'DEC-MINUTES',
        );
        $this->assertSame('minutes_recorded', $withMinutes->status);

        try {
            (new CompleteDefenseSession)($withMinutes, $approver, 'DEC-DONE');
            $this->fail('إتمام مناقشة بلا مرجع إثبات متطلبات يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $completed = (new CompleteDefenseSession)(
            $withMinutes, $approver, 'DEC-DONE', 'EVID-1',
        );
        $this->assertSame('completed', $completed->status);

        $audit = AuditLog::query()
            ->where('action', 'postgraduate.defense_completed')
            ->firstOrFail();

        $this->assertFalse($audit->after['is_graduation_decision']);
        $this->assertSame('DEF-REQ-APPROVED', $session->requirements_reference);
        $this->assertSame(1, DefenseSession::count());

        try {
            $completed->delete();
            $this->fail('حذف جلسة مناقشة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    private function userWithRole(RoleCode $code, ?User $user = null): User
    {
        $user ??= User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
