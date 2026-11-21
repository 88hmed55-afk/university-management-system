<?php

namespace App\Models;

use Database\Factories\RefundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Refund extends Model
{
    /** @use HasFactory<RefundFactory> */
    use HasFactory;

    protected $fillable = [
        'refund_no', 'original_payment_id', 'amount', 'reason', 'requested_by',
        'approved_by', 'approved_at', 'executed_at', 'status', 'decision_reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Refund $refund): void {
            if (in_array($refund->getOriginal('status'), ['approved', 'executed'], true)
                && $refund->isDirty(['original_payment_id', 'amount', 'reason', 'requested_by'])) {
                throw new LogicException('الرد المعتمد/المُنفَّذ لا يُعدل.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('طلبات الرد لا تُحذف.');
        });
    }

    /** @return BelongsTo<StudentPayment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(StudentPayment::class, 'original_payment_id');
    }
}
