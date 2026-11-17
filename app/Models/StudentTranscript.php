<?php

namespace App\Models;

use Database\Factories\StudentTranscriptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class StudentTranscript extends Model
{
    /** @use HasFactory<StudentTranscriptFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id', 'study_plan_version_id', 'payload',
        'generated_by', 'generated_at', 'decision_reference',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('نسخ كشف الدرجات غير قابلة للتعديل؛ أنشئ نسخة جديدة.');
        });

        static::deleting(function (): never {
            throw new LogicException('نسخ كشف الدرجات غير قابلة للحذف.');
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
