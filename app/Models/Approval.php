<?php

namespace App\Models;

use Database\Factories\ApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class Approval extends Model
{
    /** @use HasFactory<ApprovalFactory> */
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'decision',
        'approver_id',
        'decided_at',
        'reference',
        'reason',
        'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('قرارات الموافقة غير قابلة للتعديل؛ سجّل قراراً جديداً.');
        });

        static::deleting(function (): never {
            throw new LogicException('قرارات الموافقة غير قابلة للحذف.');
        });
    }

    public function decisionLabel(): string
    {
        return trans('approvals.'.$this->decision);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
