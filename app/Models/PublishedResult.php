<?php

namespace App\Models;

use Database\Factories\PublishedResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PublishedResult extends Model
{
    /** @use HasFactory<PublishedResultFactory> */
    use HasFactory;

    protected $fillable = [
        'course_section_id', 'course_enrollment_id', 'payload', 'published_by',
        'published_at', 'decision_reference', 'withdrawn_at', 'withdrawn_by',
        'withdrawal_reason',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'published_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (PublishedResult $result): void {
            if ($result->isDirty(['payload', 'course_section_id', 'course_enrollment_id', 'published_by', 'published_at', 'decision_reference'])) {
                throw new LogicException('النتيجة المنشورة غير قابلة للتعديل؛ سحب النشر بإجراء استثنائي.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('النتائج المنشورة غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<CourseSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    /** @return BelongsTo<CourseEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }
}
