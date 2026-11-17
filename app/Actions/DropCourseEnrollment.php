<?php

namespace App\Actions;

use App\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DropCourseEnrollment
{
    public function __invoke(
        CourseEnrollment $enrollment,
        User $actor,
        string $reason,
        ?string $correlationId = null,
    ): CourseEnrollment {
        $selfService = $actor->hasActiveRole(RoleCode::Student);

        if ($selfService) {
            if ((int) $actor->person_id !== (int) $enrollment->student->person_id) {
                throw new AuthorizationException('لا يمكن للطالب الانسحاب لشخص آخر.');
            }
        } elseif (! $actor->hasActiveRole(RoleCode::RegistrationOfficer)
            && ! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('الانسحاب مقصور على التسجيل والقيادة الأكاديمية.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('سبب الانسحاب مطلوب.');
        }

        if ($enrollment->status !== EnrollmentStatus::Enrolled) {
            throw new InvalidArgumentException('التسجيل ليس في حالة نشطة.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($enrollment, $actor, $reason, $correlationId): void {
            $enrollment->status = EnrollmentStatus::Dropped;
            $enrollment->dropped_at = now();
            $enrollment->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'student.course_dropped',
                'subject_type' => CourseEnrollment::class,
                'subject_id' => (string) $enrollment->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'after' => ['status' => EnrollmentStatus::Dropped->value],
            ]);
        });

        return $enrollment->refresh();
    }
}
