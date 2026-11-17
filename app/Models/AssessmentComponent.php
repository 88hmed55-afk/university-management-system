<?php

namespace App\Models;

use Database\Factories\AssessmentComponentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AssessmentComponent extends Model
{
    /** @use HasFactory<AssessmentComponentFactory> */
    use HasFactory;

    protected $fillable = [
        'course_section_id', 'code', 'name', 'type_code', 'max_score',
        'status', 'decision_reference', 'approved_by', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('عناصر التقييم لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<AssessmentComponent> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /** @return BelongsTo<CourseSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }
}
