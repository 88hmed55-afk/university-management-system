<?php

namespace App\Actions;

use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OfferCourse
{
    public function __invoke(
        Course $course,
        AcademicTerm $term,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): CourseOffering {
        if (! $actor->hasActiveRole(RoleCode::AcademicLeadership)
            && ! $actor->hasActiveRole(RoleCode::RegistrationOfficer)) {
            throw new AuthorizationException('طرح المقررات مقصور على القيادة الأكاديمية والتسجيل.');
        }

        if (trim($decisionReference) === '') {
            throw new \InvalidArgumentException('مرجع قرار الطرح مطلوب.');
        }

        $offering = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $course, $term, $actor, $decisionReference, $correlationId, &$offering,
        ): void {
            $offering = CourseOffering::create([
                'course_id' => $course->getKey(),
                'academic_term_id' => $term->getKey(),
                'decision_reference' => $decisionReference,
                'approved_by' => $actor->getKey(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'academic.course_offered',
                'subject_type' => CourseOffering::class,
                'subject_id' => (string) $offering->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'course_id' => $course->getKey(),
                    'academic_term_id' => $term->getKey(),
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $offering->refresh();
    }
}
