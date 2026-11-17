<?php

namespace App\Models;

use Database\Factories\TeachingAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class TeachingAssignment extends Model
{
    /** @use HasFactory<TeachingAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'course_section_id', 'employee_id', 'assignment_type_code',
        'valid_from', 'valid_to', 'decision_reference', 'approved_by',
        'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (TeachingAssignment $assignment): void {
            if ($assignment->valid_to !== null
                && $assignment->valid_to->lessThanOrEqualTo($assignment->valid_from)) {
                throw new InvalidArgumentException('تاريخ نهاية التكليف يجب أن يكون بعد بدايته.');
            }

            $duplicate = static::query()
                ->where('course_section_id', $assignment->course_section_id)
                ->where('employee_id', $assignment->employee_id)
                ->when($assignment->exists, fn (Builder $query) => $query->whereKeyNot($assignment->getKey()))
                ->where('valid_from', '<=', $assignment->valid_to?->toDateString() ?? '9999-12-31')
                ->where(function (Builder $query) use ($assignment): void {
                    $query->whereNull('valid_to')
                        ->orWhere('valid_to', '>=', $assignment->valid_from->toDateString());
                })
                ->exists();

            if ($duplicate) {
                throw new InvalidArgumentException('تكليف مكرر للمدرس في الشعبة والفترة نفسها.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('تكليفات التدريس لا تُحذف؛ تُغلق بالأرشفة.');
        });
    }

    /** @return BelongsTo<CourseSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
