<?php

namespace App\Models;

use App\RecordStatus;
use App\RoleCode;
use Database\Factories\CollegeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class College extends Model
{
    /** @use HasFactory<CollegeFactory> */
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
     * @param  Builder<College>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /**
     * @param  Builder<College>  $query
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

        $collegeIds = [];

        if ($user->hasActiveRole(RoleCode::CollegeDean)) {
            $collegeIds = $user->assignedCollegeIds();
        }

        if ($user->hasActiveRole(RoleCode::DepartmentHead)) {
            foreach ($user->assignedDepartmentIds() as $departmentId) {
                $collegeId = Department::query()->find($departmentId)?->currentCollege()?->getKey();

                if ($collegeId !== null) {
                    $collegeIds[] = $collegeId;
                }
            }
        }

        $query->whereIn('id', array_values(array_unique($collegeIds)));
    }

    /**
     * @return BelongsToMany<Campus, $this>
     */
    public function campuses(): BelongsToMany
    {
        return $this->belongsToMany(Campus::class, 'campus_college_assignments')
            ->withPivot(['effective_from', 'effective_to', 'decision_reference'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_college_assignments')
            ->withPivot(['effective_from', 'effective_to', 'decision_reference'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_college_assignments')
            ->withPivot(['valid_from', 'valid_to', 'reason', 'approved_by', 'decision_reference'])
            ->withTimestamps();
    }
}
