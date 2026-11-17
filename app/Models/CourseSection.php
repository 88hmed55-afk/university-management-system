<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\CourseSectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use LogicException;

class CourseSection extends Model
{
    /** @use HasFactory<CourseSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'course_offering_id', 'code', 'capacity', 'campus_id', 'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CourseSection $section): void {
            if ($section->capacity !== null && $section->capacity < 0) {
                throw new InvalidArgumentException('سعة الشعبة لا يمكن أن تكون سالبة.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('الشعب لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<CourseSection> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /** @param Builder<CourseSection> $query */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $campusIds = $user->visibleCampusIds();

        if ($campusIds === null) {
            return;
        }

        $query->whereIn('campus_id', $campusIds);
    }

    /** @return BelongsTo<CourseOffering, $this> */
    public function offering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    /** @return BelongsTo<Campus, $this> */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /** @return HasMany<SectionMeeting, $this> */
    public function meetings(): HasMany
    {
        return $this->hasMany(SectionMeeting::class);
    }

    /** @return HasMany<TeachingAssignment, $this> */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }
}
