<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'title', 'description', 'department_id', 'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('المقررات لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<Course> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return HasMany<CourseVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(CourseVersion::class);
    }

    /** @return HasMany<StudyPlanCourse, $this> */
    public function planCourses(): HasMany
    {
        return $this->hasMany(StudyPlanCourse::class);
    }

    /** @return HasMany<CoursePrerequisite, $this> */
    public function prerequisites(): HasMany
    {
        return $this->hasMany(CoursePrerequisite::class);
    }
}
