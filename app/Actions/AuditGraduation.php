<?php

namespace App\Actions;

use App\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\GraduationAudit;
use App\Models\Student;
use App\Models\StudyPlanVersion;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AuditGraduation
{
    /**
     * Record a graduation check against a specific study plan version.
     * No pass/credit rules are invented; the payload holds only the inputs and
     * the completed-course list for a university-approved decision.
     */
    public function __invoke(
        Student $student,
        StudyPlanVersion $plan,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): GraduationAudit {
        if (! $actor->hasActiveRole(RoleCode::RegistrationOfficer)
            && ! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('فحص التخرج مقصور على التسجيل والقيادة الأكاديمية.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع فحص التخرج مطلوب.');
        }

        if ($plan->status !== 'approved') {
            throw new InvalidArgumentException('فحص التخرج يتم على نسخة خطة معتمدة.');
        }

        $completedCourseIds = $student->courseEnrollments()
            ->where('status', EnrollmentStatus::Completed->value)
            ->with('section.offering')
            ->get()
            ->pluck('section.offering.course_id')
            ->all();

        $payload = [
            'student_id' => $student->getKey(),
            'study_plan_version_id' => $plan->getKey(),
            'completed_course_ids' => $completedCourseIds,
            'plan_course_ids' => $plan->planCourses()->pluck('course_id')->all(),
        ];

        $audit = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $student, $plan, $actor, $decisionReference, $payload, $correlationId, &$audit,
        ): void {
            $audit = GraduationAudit::create([
                'student_id' => $student->getKey(),
                'study_plan_version_id' => $plan->getKey(),
                'payload' => $payload,
                'decision_reference' => $decisionReference,
                'audited_by' => $actor->getKey(),
                'audited_at' => now(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'student.graduation_audited',
                'subject_type' => GraduationAudit::class,
                'subject_id' => (string) $audit->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'student_id' => $student->getKey(),
                    'study_plan_version_id' => $plan->getKey(),
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $audit->refresh();
    }
}
