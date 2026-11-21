<?php

namespace App\Support;

use App\Models\PostgraduateRecord;
use App\Models\User;
use App\RoleCode;

final class PostgraduateAccess
{
    /** Creating files, nominations and scheduling is a graduate-studies office act. */
    public static function canManage(User $actor): bool
    {
        return $actor->hasActiveRole(RoleCode::GraduateStudiesCoordinator)
            || $actor->hasActiveRole(RoleCode::AcademicLeadership)
            || $actor->hasActiveRole(RoleCode::SystemAdmin);
    }

    /** Approvals require the graduate-studies authority; separation of duties is checked per action. */
    public static function canApprove(User $actor): bool
    {
        return $actor->hasActiveRole(RoleCode::GraduateStudiesCoordinator)
            || $actor->hasActiveRole(RoleCode::AcademicLeadership)
            || $actor->hasActiveRole(RoleCode::SystemAdmin);
    }

    /** Supervisors evaluate milestones; the office may also record an evaluation. */
    public static function canEvaluate(User $actor): bool
    {
        return $actor->hasActiveRole(RoleCode::FacultyMember) || self::canApprove($actor);
    }

    /** A researcher sees only their own file; staff act only within their authority. */
    public static function canViewRecord(User $actor, PostgraduateRecord $record): bool
    {
        return $record->belongsToUser($actor) || self::canManage($actor);
    }
}
