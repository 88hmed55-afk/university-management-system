<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentStatusHistory;
use App\Models\User;
use App\RoleCode;
use App\StudentStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ChangeStudentStatus
{
    public function __invoke(
        Student $student,
        StudentStatus $newStatus,
        User $actor,
        string $reason,
        string $decisionReference,
        ?Carbon $effectiveDate = null,
        ?string $correlationId = null,
    ): StudentStatusHistory {
        if (! $actor->hasActiveRole(RoleCode::AdmissionsRegistrationDirector)
            && ! $actor->hasActiveRole(RoleCode::RegistrationOfficer)) {
            throw new AuthorizationException('تغيير حالة الطالب مقصور على القبول والتسجيل المخولين.');
        }

        if (trim($reason) === '' || trim($decisionReference) === '') {
            throw new InvalidArgumentException('السبب ومرجع القرار مطلوبان لتغيير الحالة.');
        }

        $correlationId ??= Str::uuid()->toString();
        $history = null;
        $student->refresh();

        DB::transaction(function () use (
            $student, $newStatus, $actor, $reason, $decisionReference,
            $effectiveDate, $correlationId, &$history,
        ): void {
            $from = $student->status;

            $history = StudentStatusHistory::create([
                'student_id' => $student->getKey(),
                'from_status' => $from->value,
                'to_status' => $newStatus->value,
                'reason' => $reason,
                'effective_date' => $effectiveDate ?? today(),
                'decision_reference' => $decisionReference,
                'actor_id' => $actor->getKey(),
                'correlation_id' => $correlationId,
            ]);

            $student->status = $newStatus;
            $student->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'student.status_changed',
                'subject_type' => Student::class,
                'subject_id' => (string) $student->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'after' => [
                    'from' => $from->value,
                    'to' => $newStatus->value,
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $history->refresh();
    }
}
