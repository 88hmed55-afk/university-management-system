<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DefineCourse
{
    public function __invoke(
        User $actor,
        string $code,
        string $title,
        ?Department $department = null,
        ?string $description = null,
        ?string $correlationId = null,
    ): Course {
        if (! $actor->hasActiveRole(RoleCode::AcademicLeadership)
            && ! $actor->hasActiveRole(RoleCode::DepartmentHead)) {
            throw new AuthorizationException('تعريف المقررات مقصور على القيادة الأكاديمية ورؤساء الأقسام.');
        }

        $course = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $code, $title, $department, $description, $correlationId, &$course,
        ): void {
            $course = Course::create([
                'code' => $code,
                'title' => $title,
                'description' => $description,
                'department_id' => $department?->getKey(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'academic.course_defined',
                'subject_type' => Course::class,
                'subject_id' => (string) $course->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['code' => $code, 'department_id' => $department?->getKey()],
            ]);
        });

        return $course->refresh();
    }
}
