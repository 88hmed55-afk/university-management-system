<?php

namespace App\Models;

use Database\Factories\StudentPlanAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class StudentPlanAssignment extends Model
{
    /** @use HasFactory<StudentPlanAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id', 'study_plan_version_id', 'effective_from', 'effective_to',
        'decision_reference', 'actor_id', 'status', 'archived_at',
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
        static::saving(function (StudentPlanAssignment $assignment): void {
            if ($assignment->effective_to !== null
                && $assignment->effective_to->lessThanOrEqualTo($assignment->effective_from)) {
                throw new InvalidArgumentException('تاريخ نهاية ربط الخطة يجب أن يكون بعد بدايتها.');
            }

            $overlap = static::query()
                ->where('student_id', $assignment->student_id)
                ->where('status', 'active')
                ->when($assignment->exists, fn (Builder $query) => $query->whereKeyNot($assignment->getKey()))
                ->where('effective_from', '<=', $assignment->effective_to?->toDateString() ?? '9999-12-31')
                ->where(function (Builder $query) use ($assignment): void {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $assignment->effective_from->toDateString());
                })
                ->exists();

            if ($overlap) {
                throw new InvalidArgumentException('لا يجوز ربط الطالب بخطتين نشطتين في الفترة نفسها.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('ربط خطط الطلاب لا يُحذف؛ يُغلق بتاريخ النهاية.');
        });
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<StudyPlanVersion, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(StudyPlanVersion::class, 'study_plan_version_id');
    }
}
