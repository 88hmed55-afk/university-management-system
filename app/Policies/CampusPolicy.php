<?php

namespace App\Policies;

use App\Models\Campus;
use App\Models\User;

class CampusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyActiveRole();
    }

    public function view(User $user, Campus $campus): bool
    {
        return Campus::query()
            ->visibleTo($user)
            ->whereKey($campus->getKey())
            ->exists();
    }
}
