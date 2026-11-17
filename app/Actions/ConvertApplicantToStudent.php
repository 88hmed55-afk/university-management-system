<?php

namespace App\Actions;

use App\Models\AcademicProgram;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\AdmissionDecision;
use App\Models\ApplicantEvent;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentProgramEnrollment;
use App\Models\StudentStatusHistory;
use App\Models\User;
use App\RoleCode;
use App\StudentStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ConvertApplicantToStudent
{
    /**
     * Idempotent conversion: an approved application yields exactly one student
     * record and one program enrollment, even when retried (docs/06 line 70).
     */
    public function __invoke(
        Application $application,
        string $studentNo,
        AcademicProgram $program,
        AcademicYear $admissionYear,
        User $actor,
        ?AcademicTerm $admissionTerm = null,
        ?string $intakeCode = null,
        ?string $correlationId = null,
    ): Student {
        if (! $actor->hasActiveRole(RoleCode::AdmissionsRegistrationDirector)
            && ! $actor->hasActiveRole(RoleCode::RegistrationOfficer)) {
            throw new AuthorizationException('تحويل المتقدمين مقصور على القبول والتسجيل المخولين.');
        }

        $existing = Student::query()->where('source_application_id', $application->getKey())->first();

        if ($existing !== null) {
            return $existing;
        }

        if (! AdmissionDecision::query()
            ->where('application_id', $application->getKey())
            ->where('decision', 'approved')
            ->exists()) {
            throw new InvalidArgumentException('لا يمكن تحويل طلب غير مقبول.');
        }

        if (Student::query()->where('person_id', $application->person_id)->exists()) {
            throw new InvalidArgumentException('لدى هذا الشخص سجل طالب بالفعل.');
        }

        if (trim($studentNo) === '') {
            throw new InvalidArgumentException('الرقم الجامعي مطلوب ولا يُولَّد آلياً.');
        }

        $correlationId ??= Str::uuid()->toString();
        $student = null;

        DB::transaction(function () use (
            $application, $studentNo, $program, $admissionYear, $actor,
            $admissionTerm, $intakeCode, $correlationId, &$student,
        ): void {
            $student = Student::create([
                'person_id' => $application->person_id,
                'student_no' => $studentNo,
                'source_application_id' => $application->getKey(),
            ]);

            StudentProgramEnrollment::create([
                'student_id' => $student->getKey(),
                'academic_program_id' => $program->getKey(),
                'academic_year_id' => $admissionYear->getKey(),
                'academic_term_id' => $admissionTerm?->getKey(),
                'intake_code' => $intakeCode,
                'decision_reference' => 'APPLICATION-'.$application->getKey(),
            ]);

            StudentStatusHistory::create([
                'student_id' => $student->getKey(),
                'to_status' => StudentStatus::Active->value,
                'reason' => 'تحويل من طلب قبول مقبول',
                'effective_date' => today(),
                'decision_reference' => 'APPLICATION-'.$application->getKey(),
                'actor_id' => $actor->getKey(),
                'correlation_id' => $correlationId,
            ]);

            ApplicantEvent::create([
                'application_id' => $application->getKey(),
                'event_type' => 'converted_to_student',
                'actor_id' => $actor->getKey(),
                'correlation_id' => $correlationId,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'student.converted_from_application',
                'subject_type' => Student::class,
                'subject_id' => (string) $student->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'application_id' => $application->getKey(),
                    'person_id' => $application->person_id,
                    'academic_program_id' => $program->getKey(),
                    'academic_year_id' => $admissionYear->getKey(),
                ],
            ]);
        });

        return $student->refresh();
    }
}
