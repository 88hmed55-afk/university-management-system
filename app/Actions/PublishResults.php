<?php

namespace App\Actions;

use App\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\GradeSubmission;
use App\Models\PublishedResult;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PublishResults
{
    /**
     * Publish only an approved submission, creating immutable snapshots (docs/04 SCR-062).
     *
     * @return Collection<int, PublishedResult>
     */
    public function __invoke(
        CourseSection $section,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): Collection {
        if (! $actor->hasActiveRole(RoleCode::RegistrationOfficer)
            && ! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('نشر النتائج مقصور على التسجيل والقيادة الأكاديمية.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار النشر مطلوب.');
        }

        $approved = GradeSubmission::query()
            ->where('course_section_id', $section->getKey())
            ->where('status', 'approved')
            ->exists();

        if (! $approved) {
            throw new InvalidArgumentException('لا يمكن النشر قبل اعتماد الدرجات.');
        }

        $enrollments = CourseEnrollment::query()
            ->where('course_section_id', $section->getKey())
            ->where('status', EnrollmentStatus::Enrolled->value)
            ->get();

        $correlationId ??= Str::uuid()->toString();
        $results = collect();

        DB::transaction(function () use (
            $section, $actor, $decisionReference, $enrollments, $correlationId, &$results,
        ): void {
            foreach ($enrollments as $enrollment) {
                $scores = $enrollment->scores()->get()->map(fn ($score) => [
                    'component_id' => $score->assessment_component_id,
                    'raw_score' => $score->raw_score,
                ])->all();

                $results->push(PublishedResult::create([
                    'course_section_id' => $section->getKey(),
                    'course_enrollment_id' => $enrollment->getKey(),
                    'payload' => ['scores' => $scores],
                    'published_by' => $actor->getKey(),
                    'published_at' => now(),
                    'decision_reference' => $decisionReference,
                ]));
            }

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'grades.published',
                'subject_type' => CourseSection::class,
                'subject_id' => (string) $section->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'course_section_id' => $section->getKey(),
                    'count' => $results->count(),
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $results;
    }
}
