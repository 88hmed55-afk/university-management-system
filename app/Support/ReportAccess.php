<?php

namespace App\Support;

use App\DataScopeCode;
use App\Models\ReportDefinition;
use App\Models\User;
use App\RoleCode;

final class ReportAccess
{
    /** Defining and approving report/dashboard definitions is a governance act. */
    public static function canConfigure(User $actor): bool
    {
        return $actor->hasActiveRole(RoleCode::SystemAdmin)
            || $actor->hasActiveRole(RoleCode::UniversityPresident)
            || $actor->hasActiveRole(RoleCode::InternalAuditor);
    }

    /** @return array<int, string> */
    public static function supportedScopeCodes(): array
    {
        return [
            DataScopeCode::Institution->value,
            DataScopeCode::Campus->value,
            DataScopeCode::Self->value,
            DataScopeCode::College->value,
            DataScopeCode::Department->value,
        ];
    }

    /** Fail closed: a user with no usable scope cannot see any report rows. */
    public static function canUseScope(User $actor, string $scopeCode): bool
    {
        return match ($scopeCode) {
            DataScopeCode::Institution->value => $actor->hasInstitutionWideAccess(),
            DataScopeCode::Campus->value => $actor->visibleCampusIds() !== [],
            DataScopeCode::Self->value => $actor->person_id !== null,
            DataScopeCode::College->value => $actor->assignedCollegeIds() !== [],
            DataScopeCode::Department->value => $actor->assignedDepartmentIds() !== [],
            default => false,
        };
    }

    /** @return array<int, string> */
    public static function activeRoleCodes(User $actor): array
    {
        return $actor->activeRoles()
            ->pluck('roles.code')
            ->map(fn ($code): string => $code instanceof RoleCode ? $code->value : (string) $code)
            ->all();
    }

    /** Roles are declared on the approved definition; no permission is inferred from a title. */
    public static function canRun(ReportDefinition $definition, User $actor): bool
    {
        if ($definition->status !== 'approved' || ! self::canUseScope($actor, $definition->scope_code)) {
            return false;
        }

        return array_intersect(self::activeRoleCodes($actor), $definition->allowed_role_codes ?? []) !== [];
    }

    /** Export is a separate, explicitly granted act that still requires run authority. */
    public static function canExport(ReportDefinition $definition, User $actor): bool
    {
        if (! self::canRun($definition, $actor)) {
            return false;
        }

        return array_intersect(self::activeRoleCodes($actor), $definition->allowed_export_role_codes ?? []) !== [];
    }
}
