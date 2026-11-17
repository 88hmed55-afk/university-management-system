<?php

namespace App\Models;

use App\EnrollmentStatus;
use Database\Factories\CourseEnrollmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use LogicException;

class CourseEnrollment extends Model
{
    /** @use HasFactory<CourseEnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id', 'course_section_id', 'status', 'source', 'idempotency_key',
        'decision_reference', 'actor_id', 'enrolled_at', 'dropped_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'enrolled_at' => 'datetime',
            'dropped_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CourseEnrollment $enrollment): void {
            if ($enrollment->status !== EnrollmentStatus::Enrolled) {
                return;
            }

            $offeringId = (int) $enrollment->section->course_offering_id;

            $duplicate = static::query()
                ->where('student_id', $enrollment->student_id)
                ->where('status', EnrollmentStatus::Enrolled->value)
                ->when($enrollment->exists, fn (Builder $query) => $query->whereKeyNot($enrollment->getKey()))
                ->whereHas('section', fn (Builder $query) => $query->where('course_offering_id', $offeringId))
                ->exists();

            if ($duplicate) {
                throw new InvalidArgumentException('الطالب مسجل بالفعل في هذا المقرر للفصل نفسه.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('تسجيلات الطلاب لا تُحذف؛ تُغلق بالانسحاب.');
        });
    }

    /** @param Builder<CourseEnrollment> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', EnrollmentStatus::Enrolled->value);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<CourseSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    /** @return HasMany<AssessmentScore, $this> */
    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }
}
