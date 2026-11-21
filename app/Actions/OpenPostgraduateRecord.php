<?php

namespace App\Actions;

use App\Models\AcademicProgram;
use App\Models\AuditLog;
use App\Models\PostgraduateRecord;
use App\Models\Student;
use App\Models\User;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class OpenPostgraduateRecord
{
    /**
     * Admission requirements belong to the approved regulation (P-016).
     * This action only records the office decision; it does not infer eligibility.
     */
    public function __invoke(
        User $actor,
        string $recordNo,
        Student $student,
        AcademicProgram $program,
        string $degreeCode,
        ?string $admissionReference = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): PostgraduateRecord {
        if (! PostgraduateAccess::canManage($actor)) {
            throw new AuthorizationException('فتح ملفات الدراسات العليا مقصور على عمادة الدراسات العليا.');
        }

        if (trim($recordNo) === '' || trim($degreeCode) === '') {
            throw new InvalidArgumentException('رقم الملف ورمز الدرجة مطلوبان.');
        }

        if (trim((string) $admissionReference) === '' || trim((string) $decisionReference) === '') {
            throw new InvalidArgumentException('مرجع القبول ومرجع قرار الفتح مطلوبان؛ لا تُفترض متطلبات قبول.');
        }

        if (PostgraduateRecord::query()
            ->where('student_id', $student->getKey())
            ->where('program_id', $program->getKey())
            ->exists()) {
            throw new InvalidArgumentException('يوجد ملف دراسات عليا لهذا الطالب في البرنامج نفسه.');
        }

        $record = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $recordNo, $student, $program, $degreeCode,
            $admissionReference, $decisionReference, $correlationId, &$record,
        ): void {
            $record = PostgraduateRecord::create([
                'record_no' => $recordNo,
                'student_id' => $student->getKey(),
                'program_id' => $program->getKey(),
                'degree_code' => $degreeCode,
                'admission_reference' => $admissionReference,
                'status' => 'active',
                'decision_reference' => $decisionReference,
                'created_by' => $actor->getKey(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.record_opened',
                'subject_type' => PostgraduateRecord::class,
                'subject_id' => (string) $record->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'record_no' => $recordNo,
                    'student_id' => $student->getKey(),
                    'program_id' => $program->getKey(),
                    'degree_code' => $degreeCode,
                ],
            ]);
        });

        return $record->refresh();
    }
}
