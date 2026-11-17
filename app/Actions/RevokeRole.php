<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RevokeRole
{
    public function __invoke(
        User $user,
        Role $role,
        User $actor,
        string $reason,
        ?string $correlationId = null,
    ): User {
        if ($actor->is($user)) {
            throw new AuthorizationException('لا يجوز التفويض الذاتي.');
        }

        if (! $actor->hasActiveRole(RoleCode::IdentityAdmin) && ! $actor->hasActiveRole(RoleCode::SystemAdmin)) {
            throw new AuthorizationException('ليس لدى الجهة المنفذة صلاحية إدارة الهوية.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('سبب سحب الدور مطلوب.');
        }

        DB::transaction(function () use ($user, $role, $actor, $reason, $correlationId): void {
            $pivot = $user->roles()->whereKey($role->getKey())->first()?->pivot;

            $user->roles()->detach($role->getKey());

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'role.revoked',
                'subject_type' => Role::class,
                'subject_id' => (string) $role->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId ?? Str::uuid()->toString(),
                'reason' => $reason,
                'before' => [
                    'user_id' => $user->getKey(),
                    'role_code' => $role->code->value,
                    'valid_from' => $pivot?->valid_from,
                    'valid_to' => $pivot?->valid_to,
                ],
            ]);
        });

        return $user->refresh();
    }
}
