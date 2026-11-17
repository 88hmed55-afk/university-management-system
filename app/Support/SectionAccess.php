<?php

namespace App\Support;

use App\Models\CourseSection;
use App\Models\Employee;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\RoleCode;
use Illuminate\Database\Eloquent\Builder;

final class SectionAccess
{
    /**
     * Faculty may act only on sections they are assigned to teach;
     * registration/academic leadership/department heads may act institution-wide.
     */
    public static function canRecordOn(User $actor, CourseSection $section): bool
    {
        if ($actor->hasActiveRole(RoleCode::RegistrationOfficer)
            || $actor->hasActiveRole(RoleCode::AcademicLeadership)
            || $actor->hasActiveRole(RoleCode::DepartmentHead)) {
            return true;
        }

        if (! $actor->hasActiveRole(RoleCode::FacultyMember) || $actor->person_id === null) {
            return false;
        }

        $employee = Employee::query()->where('person_id', $actor->person_id)->first();

        if ($employee === null) {
            return false;
        }

        return TeachingAssignment::query()
            ->where('course_section_id', $section->getKey())
            ->where('employee_id', $employee->getKey())
            ->where(function (Builder $query): void {
                $query->whereNull('valid_to')->orWhere('valid_to', '>=', today());
            })
            ->where('valid_from', '<=', today())
            ->exists();
    }
}
