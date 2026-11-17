<?php

namespace App\Models;

use Database\Factories\GraduationAuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class GraduationAudit extends Model
{
    /** @use HasFactory<GraduationAuditFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id', 'study_plan_version_id', 'payload',
        'decision_reference', 'audited_by', 'audited_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'audited_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('فحوص التخرج غير قابلة للتعديل؛ أنشئ فحصاً جديداً.');
        });

        static::deleting(function (): never {
            throw new LogicException('فحوص التخرج غير قابلة للحذف.');
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
