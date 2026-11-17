<?php

namespace App\Models;

use Database\Factories\StudentProgramEnrollmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class StudentProgramEnrollment extends Model
{
    /** @use HasFactory<StudentProgramEnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id', 'academic_program_id', 'academic_year_id', 'academic_term_id',
        'intake_code', 'status', 'decision_reference', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('التحاقات الطلاب بالبرامج لا تُحذف؛ تُغلق بالأرشفة.');
        });
    }

    /** @param Builder<StudentProgramEnrollment> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<AcademicProgram, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'academic_program_id');
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }
}
