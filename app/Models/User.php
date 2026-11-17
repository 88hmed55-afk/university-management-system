<?php

namespace App\Models;

use App\DataScopeCode;
use App\RoleCode;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'person_id'])]
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
     * @return BelongsToMany<College, $this>
     */
    public function assignedColleges(): BelongsToMany
    {
        return $this->belongsToMany(College::class, 'user_college_assignments')
            ->withPivot(['valid_from', 'valid_to', 'reason', 'approved_by', 'decision_reference'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Department, $this>
     */
    public function assignedDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'user_department_assignments')
            ->withPivot(['valid_from', 'valid_to', 'reason', 'approved_by', 'decision_reference'])
            ->withTimestamps();
    }

    /**
     * @return array<int, int>
     */
    public function assignedCollegeIds(): array
    {
        return $this->assignedColleges()
            ->wherePivot('valid_from', '<=', today())
            ->where(function ($query): void {
                $query->whereNull('user_college_assignments.valid_to')
                    ->orWhere('user_college_assignments.valid_to', '>', today());
            })
            ->pluck('colleges.id')
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function assignedDepartmentIds(): array
    {
        return $this->assignedDepartments()
            ->wherePivot('valid_from', '<=', today())
            ->where(function ($query): void {
                $query->whereNull('user_department_assignments.valid_to')
                    ->orWhere('user_department_assignments.valid_to', '>', today());
            })
            ->pluck('departments.id')
            ->all();
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
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

    /**
     * Null means institution-wide; an empty array means no active role.
     *
     * @return array<int, int>|null
     */
    public function visibleCampusIds(): ?array
    {
        if (! $this->hasAnyActiveRole()) {
            return [];
        }

        if ($this->hasInstitutionWideAccess()) {
            return null;
        }

        return $this->campuses()->pluck('campuses.id')->all();
    }
}
