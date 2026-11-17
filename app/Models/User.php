<?php

namespace App\Models;

use App\DataScopeCode;
use App\RoleCode;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['granted_by', 'valid_from', 'valid_to', 'reason'])
            ->withTimestamps();
    }

    /**
     * Only roles whose delegation window is currently open.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function activeRoles(): BelongsToMany
    {
        return $this->roles()
            ->where(function ($query): void {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>', now());
            });
    }

    /**
     * @return BelongsToMany<Campus, $this>
     */
    public function campuses(): BelongsToMany
    {
        return $this->belongsToMany(Campus::class, 'user_campus_assignments')
            ->withTimestamps();
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function hasActiveRole(RoleCode $roleCode): bool
    {
        return $this->activeRoles()
            ->where('roles.code', $roleCode->value)
            ->exists();
    }

    public function hasAnyActiveRole(): bool
    {
        return $this->activeRoles()->exists();
    }

    /**
     * An institution-wide role is the only case where campus rows are not restricted.
     */
    public function hasInstitutionWideAccess(): bool
    {
        return $this->activeRoles()
            ->whereHas('dataScope', function ($query): void {
                $query->where('code', DataScopeCode::Institution->value);
            })
            ->exists();
    }
}
