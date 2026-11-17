<?php

namespace App\Models;

use Database\Factories\StudyPlanVersionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class StudyPlanVersion extends Model
{
    /** @use HasFactory<StudyPlanVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_program_id', 'version_no', 'name', 'approved_at',
        'effective_from', 'effective_to', 'status', 'decision_reference',
        'approved_by', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (StudyPlanVersion $version): void {
            if ($version->getOriginal('status') === 'approved'
                && $version->isDirty(['version_no', 'effective_from', 'effective_to', 'approved_at'])) {
                throw new LogicException('نسخة الخطة المعتمدة لا تُعدل؛ أنشئ نسخة جديدة.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('نسخ الخطط لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<StudyPlanVersion> $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    /** @return BelongsTo<AcademicProgram, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'academic_program_id');
    }

    /** @return HasMany<StudyPlanCourse, $this> */
    public function planCourses(): HasMany
    {
        return $this->hasMany(StudyPlanCourse::class);
    }

    /** @return HasMany<StudentPlanAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(StudentPlanAssignment::class);
    }
}
