<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\GradeApproval;
use App\Models\GradeSubmission;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ReviewGradeSubmission
{
    /**
     * Separation of duties: the approver must be a different authorized actor
     * from the submitter (docs/04 SCR-061).
     */
    public function __invoke(
        GradeSubmission $submission,
        User $actor,
        string $decision,
        string $decisionReference,
        ?string $reason = null,
        ?string $correlationId = null,
    ): GradeApproval {
        if (! $actor->hasActiveRole(RoleCode::DepartmentHead)
            && ! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('اعتماد الدرجات مقصور على رؤساء الأقسام والقيادة الأكاديمية.');
        }

        if ((int) $submission->submitted_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لفاعل واحد الإدخال والاعتماد.');
        }

        if (! in_array($decision, ['approved', 'returned', 'rejected'], true)) {
            throw new InvalidArgumentException('قرار غير صالح.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع القرار مطلوب.');
        }

        if ($submission->status !== 'submitted') {
            throw new InvalidArgumentException('التقديم ليس في حالة انتظار المراجعة.');
        }

        $approval = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $submission, $actor, $decision, $decisionReference,
            $reason, $correlationId, &$approval,
        ): void {
            $approval = GradeApproval::create([
                'grade_submission_id' => $submission->getKey(),
                'decision' => $decision,
                'approver_id' => $actor->getKey(),
                'reason' => $reason,
                'decision_reference' => $decisionReference,
            ]);

            $submission->status = $decision === 'approved' ? 'approved' : 'returned';
            $submission->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'grades.reviewed',
                'subject_type' => GradeSubmission::class,
                'subject_id' => (string) $submission->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'after' => [
                    'decision' => $decision,
                    'decision_reference' => $decisionReference,
                    'submitted_by' => $submission->submitted_by,
                ],
            ]);
        });

        return $approval->refresh();
    }
}
