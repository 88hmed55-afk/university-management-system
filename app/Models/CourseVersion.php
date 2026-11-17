<?php

namespace App\Models;

use Database\Factories\CourseVersionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CourseVersion extends Model
{
    /** @use HasFactory<CourseVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id', 'version_no', 'description', 'outcomes', 'content',
        'effective_from', 'effective_to', 'status', 'decision_reference',
        'approved_by', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (CourseVersion $version): void {
            if ($version->getOriginal('status') === 'approved'
                && $version->isDirty(['version_no', 'effective_from', 'effective_to'])) {
                throw new LogicException('نسخة المقرر المعتمدة لا تُعدل؛ أنشئ نسخة جديدة.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('نسخ المقررات لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<CourseVersion> $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
