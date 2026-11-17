<?php

namespace App\Policies;

use App\Models\PersonIdentifier;
use App\Models\User;

class PersonIdentifierPolicy
{
    /**
     * Official identifiers are highly sensitive (docs/09 line 36).
     * Access is fail-closed and never inferred from a role name (docs/05 line 48):
     * the university must first approve an explicit list of user IDs.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->getKey(), config('privacy.identifier_viewer_user_ids', []), true);
    }

    public function view(User $user, PersonIdentifier $identifier): bool
    {
        return $this->viewAny($user);
    }
}
