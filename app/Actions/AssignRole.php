<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AssignRole
{
    public function __invoke(
        User $user,
        Role $role,
        User $actor,
        string $reason,
        ?Carbon $validFrom = null,
        ?Carbon $validTo = null,
        ?string $correlationId = null,
    ): User {
        $this->authorizeActor($actor, $user);

        if (trim($reason) === '') {
            throw new InvalidArgumentException('سبب إسناد الدور مطلوب.');
        }

        if ($validFrom !== null && $validTo !== null && $validTo->lessThanOrEqualTo($validFrom)) {
            throw new InvalidArgumentException('تاريخ نهاية التفويض يجب أن يكون بعد تاريخ بدايته.');
        }

        DB::transaction(function () use ($user, $role, $actor, $reason, $validFrom, $validTo, $correlationId): void {
            $user->roles()->syncWithoutDetaching([
                $role->getKey() => [
                    'granted_by' => $actor->getKey(),
                    'valid_from' => $validFrom,
                    'valid_to' => $validTo,
                    'reason' => $reason,
                ],
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'role.assigned',
                'subject_type' => Role::class,
                'subject_id' => (string) $role->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId ?? Str::uuid()->toString(),
                'reason' => $reason,
                'after' => [
                    'user_id' => $user->getKey(),
                    'role_code' => $role->code->value,
                    'valid_from' => $validFrom?->toIso8601String(),
                    'valid_to' => $validTo?->toIso8601String(),
                ],
            ]);
        });

        return $user->refresh();
    }

    private function authorizeActor(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            throw new AuthorizationException('لا يجوز التفويض الذاتي.');
        }

        if (! $actor->hasActiveRole(RoleCode::IdentityAdmin) && ! $actor->hasActiveRole(RoleCode::SystemAdmin)) {
            throw new AuthorizationException('ليس لدى الجهة المنفذة صلاحية إدارة الهوية.');
        }
    }
}
