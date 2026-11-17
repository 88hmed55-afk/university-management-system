<?php

namespace App\Models;

use App\RecordStatus;
use App\RoleCode;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'status',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Department>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /**
     * @param  Builder<Department>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! $user->hasAnyActiveRole()) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($user->hasInstitutionWideAccess()) {
            return;
        }

        $departmentIds = [];

        if ($user->hasActiveRole(RoleCode::DepartmentHead)) {
            $departmentIds = $user->assignedDepartmentIds();
        }

        if ($user->hasActiveRole(RoleCode::CollegeDean)) {
            $collegeIds = $user->assignedCollegeIds();

            foreach (Department::query()->with('colleges')->get() as $department) {
                $currentCollegeId = $department->currentCollege()?->getKey();

                if ($currentCollegeId !== null && in_array($currentCollegeId, $collegeIds, true)) {
                    $departmentIds[] = $department->getKey();
                }
            }
        }

        $query->whereIn('id', array_values(array_unique($departmentIds)));
    }

    /**
     * The owning college for the current period, without deleting older ownership.
     */
    public function currentCollege(): ?College
    {
        return $this->colleges()
            ->wherePivot('effective_from', '<=', today())
            ->where(function ($query): void {
                $query->whereNull('department_college_assignments.effective_to')
                    ->orWhere('department_college_assignments.effective_to', '>', today());
            })
            ->first();
    }

    /**
     * @return BelongsToMany<College, $this>
     */
    public function colleges(): BelongsToMany
    {
        return $this->belongsToMany(College::class, 'department_college_assignments')
            ->withPivot(['effective_from', 'effective_to', 'decision_reference'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_department_assignments')
            ->withPivot(['valid_from', 'valid_to', 'reason', 'approved_by', 'decision_reference'])
            ->withTimestamps();
    }
}
