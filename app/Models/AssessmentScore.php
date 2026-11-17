<?php

namespace App\Models;

use Database\Factories\AssessmentScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AssessmentScore extends Model
{
    /** @use HasFactory<AssessmentScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'course_enrollment_id', 'assessment_component_id', 'raw_score',
        'entered_by', 'entered_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'raw_score' => 'decimal:2',
            'entered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('الدرجات لا تُحذف؛ تُعدَّل بإجراء موثّق قبل الاعتماد.');
        });
    }

    /** @return BelongsTo<CourseEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }

    /** @return BelongsTo<AssessmentComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id');
    }
}
