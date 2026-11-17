<?php

namespace App\Models;

use Database\Factories\GradeApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class GradeApproval extends Model
{
    /** @use HasFactory<GradeApprovalFactory> */
    use HasFactory;

    protected $fillable = [
        'grade_submission_id', 'decision', 'approver_id',
        'reason', 'decision_reference',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('قرارات اعتماد الدرجات غير قابلة للتعديل.');
        });

        static::deleting(function (): never {
            throw new LogicException('قرارات اعتماد الدرجات غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<GradeSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(GradeSubmission::class, 'grade_submission_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
