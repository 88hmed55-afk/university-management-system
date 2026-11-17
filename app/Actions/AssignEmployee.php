<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\College;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentAssignment;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AssignEmployee
{
    /**
     * Create a dated employment assignment. The model enforces the single direct
     * manager rule; this action adds HR authorization and an audit record.
     */
    public function __invoke(
        Employee $employee,
        User $actor,
        Carbon $validFrom,
        ?Carbon $validTo,
        string $decisionReference,
        ?Employee $manager = null,
        ?Employee $supervisor = null,
        ?Campus $campus = null,
        ?College $college = null,
        ?Department $department = null,
        ?string $assignmentTypeCode = null,
        ?string $reason = null,
        ?string $correlationId = null,
    ): EmploymentAssignment {
        if (! $actor->hasActiveRole(RoleCode::HrDirector) && ! $actor->hasActiveRole(RoleCode::HrOfficer)) {
            throw new AuthorizationException('تعديل التعيينات مقصور على الموارد البشرية المخولين.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار التعيين مطلوب.');
        }

        $assignment = null;

        DB::transaction(function () use (
            $employee, $actor, $validFrom, $validTo, $decisionReference,
            $manager, $supervisor, $campus, $college, $department,
            $assignmentTypeCode, $reason, $correlationId, &$assignment,
        ): void {
            $assignment = EmploymentAssignment::create([
                'employee_id' => $employee->getKey(),
                'manager_employee_id' => $manager?->getKey(),
                'supervisor_employee_id' => $supervisor?->getKey(),
                'assignment_type_code' => $assignmentTypeCode,
                'college_id' => $college?->getKey(),
                'department_id' => $department?->getKey(),
                'campus_id' => $campus?->getKey(),
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'decision_reference' => $decisionReference,
                'approved_by' => $actor->getKey(),
                'reason' => $reason,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'employment_assignment.created',
                'subject_type' => EmploymentAssignment::class,
                'subject_id' => (string) $assignment->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId ?? Str::uuid()->toString(),
                'reason' => $reason,
                'after' => [
                    'employee_id' => $employee->getKey(),
                    'manager_employee_id' => $manager?->getKey(),
                    'campus_id' => $campus?->getKey(),
                    'valid_from' => $validFrom->toDateString(),
                    'valid_to' => $validTo?->toDateString(),
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $assignment->refresh();
    }
}
