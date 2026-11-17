<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentPlanAssignment;
use App\Models\StudyPlanVersion;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AssignStudentPlan
{
    public function __invoke(
        Student $student,
        StudyPlanVersion $version,
        User $actor,
        string $decisionReference,
        Carbon $effectiveFrom,
        ?Carbon $effectiveTo = null,
        ?string $correlationId = null,
    ): StudentPlanAssignment {
        if (! $actor->hasActiveRole(RoleCode::RegistrationOfficer)
            && ! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('ربط الطالب بخطة دراسة مقصور على التسجيل والقيادة الأكاديمية.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار ربط الخطة مطلوب.');
        }

        if ($version->status !== 'approved') {
            throw new InvalidArgumentException('لا يمكن ربط الطالب بخطة غير معتمدة.');
        }

        $correlationId ??= Str::uuid()->toString();
        $assignment = null;

        DB::transaction(function () use (
            $student, $version, $actor, $decisionReference,
            $effectiveFrom, $effectiveTo, $correlationId, &$assignment,
        ): void {
            $assignment = StudentPlanAssignment::create([
                'student_id' => $student->getKey(),
                'study_plan_version_id' => $version->getKey(),
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'decision_reference' => $decisionReference,
                'actor_id' => $actor->getKey(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'student.plan_assigned',
                'subject_type' => StudentPlanAssignment::class,
                'subject_id' => (string) $assignment->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'student_id' => $student->getKey(),
                    'study_plan_version_id' => $version->getKey(),
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $assignment->refresh();
    }
}
