<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\CourseSection;
use App\Models\Employee;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AssignInstructor
{
    public function __invoke(
        CourseSection $section,
        Employee $instructor,
        User $actor,
        string $decisionReference,
        Carbon $validFrom,
        ?Carbon $validTo = null,
        ?string $assignmentTypeCode = null,
        ?string $correlationId = null,
    ): TeachingAssignment {
        if (! $actor->hasActiveRole(RoleCode::AcademicLeadership)
            && ! $actor->hasActiveRole(RoleCode::DepartmentHead)) {
            throw new AuthorizationException('إسناد المدرسين مقصور على القيادة الأكاديمية ورؤساء الأقسام.');
        }

        if (trim($decisionReference) === '') {
            throw new \InvalidArgumentException('مرجع قرار الإسناد مطلوب.');
        }

        $assignment = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $section, $instructor, $actor, $decisionReference,
            $validFrom, $validTo, $assignmentTypeCode, $correlationId, &$assignment,
        ): void {
            $assignment = TeachingAssignment::create([
                'course_section_id' => $section->getKey(),
                'employee_id' => $instructor->getKey(),
                'assignment_type_code' => $assignmentTypeCode,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'decision_reference' => $decisionReference,
                'approved_by' => $actor->getKey(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'academic.instructor_assigned',
                'subject_type' => TeachingAssignment::class,
                'subject_id' => (string) $assignment->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'course_section_id' => $section->getKey(),
                    'employee_id' => $instructor->getKey(),
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $assignment->refresh();
    }
}
