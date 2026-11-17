<?php

namespace App\Actions;

use App\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\CourseEnrollment;
use App\Models\CoursePrerequisite;
use App\Models\CourseSection;
use App\Models\SectionMeeting;
use App\Models\Student;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class EnrollStudentInSection
{
    public function __invoke(
        Student $student,
        CourseSection $section,
        User $actor,
        ?string $idempotencyKey = null,
        ?string $decisionReference = null,
    ): CourseEnrollment {
        $selfService = $actor->hasActiveRole(RoleCode::Student);

        if ($selfService) {
            if ((int) $actor->person_id !== (int) $student->person_id) {
                throw new AuthorizationException('لا يمكن للطالب التسجيل لشخص آخر.');
            }
            $source = 'self';
        } else {
            if (! $actor->hasActiveRole(RoleCode::RegistrationOfficer)
                && ! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
                throw new AuthorizationException('تسجيل الطلاب مقصور على التسجيل والقيادة الأكاديمية.');
            }
            $source = 'admin';
        }

        if ($idempotencyKey !== null) {
            $byKey = CourseEnrollment::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($byKey !== null) {
                return $byKey;
            }
        }

        $already = CourseEnrollment::query()
            ->where('student_id', $student->getKey())
            ->where('course_section_id', $section->getKey())
            ->where('status', EnrollmentStatus::Enrolled->value)
            ->first();

        if ($already !== null) {
            return $already;
        }

        if ($section->capacity !== null
            && CourseEnrollment::query()->where('course_section_id', $section->getKey())->count() >= $section->capacity) {
            throw new InvalidArgumentException('اكتملت سعة الشعبة.');
        }

        $courseId = (int) $section->offering->course_id;

        $prerequisiteCourseIds = CoursePrerequisite::query()
            ->where('course_id', $courseId)
            ->pluck('prerequisite_course_id')
            ->all();

        foreach ($prerequisiteCourseIds as $prerequisiteCourseId) {
            $completed = CourseEnrollment::query()
                ->where('student_id', $student->getKey())
                ->where('status', EnrollmentStatus::Completed->value)
                ->whereHas('section', function (Builder $query) use ($prerequisiteCourseId): void {
                    $query->whereHas('offering', fn (Builder $query) => $query->where('course_id', $prerequisiteCourseId));
                })
                ->exists();

            if (! $completed) {
                throw new InvalidArgumentException('لم يستوفِ الطالب متطلبات المقرر.');
            }
        }

        $this->assertNoScheduleConflict($student, $section);

        $correlationId = Str::uuid()->toString();
        $enrollment = null;

        DB::transaction(function () use (
            $student, $section, $actor, $source, $idempotencyKey,
            $decisionReference, $correlationId, &$enrollment,
        ): void {
            $enrollment = CourseEnrollment::create([
                'student_id' => $student->getKey(),
                'course_section_id' => $section->getKey(),
                'status' => EnrollmentStatus::Enrolled->value,
                'source' => $source,
                'idempotency_key' => $idempotencyKey,
                'decision_reference' => $decisionReference,
                'actor_id' => $actor->getKey(),
                'enrolled_at' => now(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'student.course_enrolled',
                'subject_type' => CourseEnrollment::class,
                'subject_id' => (string) $enrollment->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'student_id' => $student->getKey(),
                    'course_section_id' => $section->getKey(),
                    'source' => $source,
                ],
            ]);
        });

        return $enrollment->refresh();
    }

    private function assertNoScheduleConflict(Student $student, CourseSection $section): void
    {
        $enrolledSectionIds = CourseEnrollment::query()
            ->where('student_id', $student->getKey())
            ->where('status', EnrollmentStatus::Enrolled->value)
            ->pluck('course_section_id')
            ->all();

        if ($enrolledSectionIds === []) {
            return;
        }

        foreach (SectionMeeting::query()->where('course_section_id', $section->getKey())->get() as $meeting) {
            $conflict = SectionMeeting::query()
                ->whereIn('course_section_id', $enrolledSectionIds)
                ->where('day_of_week', $meeting->day_of_week)
                ->where('start_time', '<', (string) $meeting->end_time)
                ->where('end_time', '>', (string) $meeting->start_time)
                ->where('valid_from', '<=', $meeting->valid_to?->toDateString() ?? '9999-12-31')
                ->where(function (Builder $query) use ($meeting): void {
                    $query->whereNull('valid_to')
                        ->orWhere('valid_to', '>=', $meeting->valid_from->toDateString());
                })
                ->exists();

            if ($conflict) {
                throw new InvalidArgumentException('تعارض بين جدول الشعبة وجدول الطالب.');
            }
        }
    }
}
