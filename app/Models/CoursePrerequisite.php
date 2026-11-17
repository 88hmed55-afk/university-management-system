<?php

namespace App\Models;

use Database\Factories\CoursePrerequisiteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class CoursePrerequisite extends Model
{
    /** @use HasFactory<CoursePrerequisiteFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id', 'prerequisite_course_id', 'condition_type_code',
        'decision_reference', 'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (CoursePrerequisite $prerequisite): void {
            $courseId = (int) $prerequisite->course_id;
            $requiredId = (int) $prerequisite->prerequisite_course_id;

            if ($courseId === $requiredId) {
                throw new InvalidArgumentException('لا يجوز أن يكون المقرر متطلباً لنفسه.');
            }

            $cycle = static::query()
                ->where('course_id', $requiredId)
                ->where('prerequisite_course_id', $courseId)
                ->when($prerequisite->exists, fn (Builder $query) => $query->whereKeyNot($prerequisite->getKey()))
                ->exists();

            if ($cycle) {
                throw new InvalidArgumentException('دورة متطلبات المقررات غير مسموحة.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('متطلبات المقررات لا تُحذف؛ تُغلق بالأرشفة.');
        });
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<Course, $this> */
    public function prerequisiteCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'prerequisite_course_id');
    }
}
