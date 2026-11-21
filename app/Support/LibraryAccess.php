<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Student;
use App\Models\User;
use App\RoleCode;

final class LibraryAccess
{
    /** Catalog browsing is available to any active member; beneficiary records are never public. */
    public static function canViewCatalog(User $actor): bool
    {
        return $actor->hasAnyActiveRole();
    }

    /** Circulation and catalog maintenance are a librarian act, never a self-service act. */
    public static function canManage(User $actor): bool
    {
        return $actor->hasActiveRole(RoleCode::Librarian)
            || $actor->hasActiveRole(RoleCode::SystemAdmin);
    }

    /**
     * SCR-082: the catalog must not expose another beneficiary's records.
     * A beneficiary may see only their own file; staff may see files within their authorization.
     */
    public static function canViewBeneficiary(User $actor, string $type, int $id): bool
    {
        if (self::canManage($actor)) {
            return true;
        }

        $personId = match ($type) {
            Student::class => Student::query()->whereKey($id)->value('person_id'),
            Employee::class => Employee::query()->whereKey($id)->value('person_id'),
            default => null,
        };

        return $personId !== null && (int) $personId === (int) $actor->person_id;
    }
}
