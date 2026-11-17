<?php

namespace App\Models;

use Database\Factories\StudyPlanCourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class StudyPlanCourse extends Model
{
    /** @use HasFactory<StudyPlanCourseFactory> */
    use HasFactory;

    protected $fillable = [
        'study_plan_version_id', 'course_id', 'course_type_code',
        'credits', 'level_code', 'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('مقررات الخطة لا تُحذف؛ تُغلق بالأرشفة.');
        });
    }

    /** @return BelongsTo<StudyPlanVersion, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(StudyPlanVersion::class, 'study_plan_version_id');
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
