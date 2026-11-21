<?php

namespace App\Support;

use App\Models\User;
use App\RoleCode;

final class ServicesAccess
{
    /** Defining request types, workflows and templates is an operations/configuration act. */
    public static function canConfigure(User $actor): bool
    {
        return $actor->hasActiveRole(RoleCode::SystemAdmin)
            || $actor->hasActiveRole(RoleCode::AdmissionsRegistrationDirector);
    }

    /** Handling service requests is a student-services act, not a self-service approval. */
    public static function canManageRequests(User $actor): bool
    {
        return self::canConfigure($actor)
            || $actor->hasActiveRole(RoleCode::RegistrationOfficer)
            || $actor->hasActiveRole(RoleCode::CampusManager);
    }

    /** Publishing templates and announcements needs a higher, separate authority. */
    public static function canPublish(User $actor): bool
    {
        return $actor->hasActiveRole(RoleCode::SystemAdmin)
            || $actor->hasActiveRole(RoleCode::UniversityPresident)
            || $actor->hasActiveRole(RoleCode::AdmissionsRegistrationDirector);
    }

    /** Internal notifications may be sent by student services and operations. */
    public static function canSendNotifications(User $actor): bool
    {
        return self::canManageRequests($actor);
    }
}
