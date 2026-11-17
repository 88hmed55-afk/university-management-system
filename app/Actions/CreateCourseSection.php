<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\CourseOffering;
use App\Models\CourseSection;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateCourseSection
{
    public function __invoke(
        CourseOffering $offering,
        User $actor,
        string $code,
        ?int $capacity = null,
        ?Campus $campus = null,
        ?string $correlationId = null,
    ): CourseSection {
        if (! $actor->hasActiveRole(RoleCode::RegistrationOfficer)
            && ! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('إنشاء الشعب مقصور على التسجيل والقيادة الأكاديمية.');
        }

        $section = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $offering, $actor, $code, $capacity, $campus, $correlationId, &$section,
        ): void {
            $section = CourseSection::create([
                'course_offering_id' => $offering->getKey(),
                'code' => $code,
                'capacity' => $capacity,
                'campus_id' => $campus?->getKey(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'academic.section_created',
                'subject_type' => CourseSection::class,
                'subject_id' => (string) $section->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'course_offering_id' => $offering->getKey(),
                    'code' => $code,
                    'campus_id' => $campus?->getKey(),
                ],
            ]);
        });

        return $section->refresh();
    }
}
