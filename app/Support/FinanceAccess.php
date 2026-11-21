<?php

namespace App\Support;

use App\Models\User;
use App\RoleCode;

final class FinanceAccess
{
    /** Cashier and finance leadership may capture payments and open/close sessions. */
    public static function canCapture(User $actor): bool
    {
        return $actor->hasActiveRole(RoleCode::Cashier)
            || $actor->hasActiveRole(RoleCode::FinanceDirector)
            || $actor->hasActiveRole(RoleCode::FinancialLeadership);
    }

    /** Approval/review requires finance leadership with separation of duties. */
    public static function canApprove(User $actor): bool
    {
        return $actor->hasActiveRole(RoleCode::FinanceDirector)
            || $actor->hasActiveRole(RoleCode::FinancialLeadership);
    }
}
