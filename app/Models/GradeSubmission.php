<?php

namespace App\Models;

use Database\Factories\GradeSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class GradeSubmission extends Model
{
    /** @use HasFactory<GradeSubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'course_section_id', 'submitted_by', 'submitted_at', 'status',
        'notes', 'decision_reference', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (GradeSubmission $submission): void {
            if ($submission->getOriginal('status') === 'approved') {
                throw new LogicException('التقديم المعتمد لا يُعدل؛ أنشئ تعديلاً موثّقاً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('تقديمات الدرجات لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @return BelongsTo<CourseSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return HasMany<GradeApproval, $this> */
    public function approvals(): HasMany
    {
        return $this->hasMany(GradeApproval::class);
    }
}
