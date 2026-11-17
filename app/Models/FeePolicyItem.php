<?php

namespace App\Models;

use Database\Factories\FeePolicyItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class FeePolicyItem extends Model
{
    /** @use HasFactory<FeePolicyItemFactory> */
    use HasFactory;

    protected $fillable = [
        'fee_policy_id', 'fee_item_id', 'amount', 'discount_amount',
        'due_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FeePolicyItem $item): void {
            if ($item->exists && $item->policy->status !== 'draft') {
                throw new LogicException('بنود إصدار الرسوم المعتمد لا تُعدل.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('بنود سياسة الرسوم لا تُحذف.');
        });
    }

    /** @return BelongsTo<FeePolicy, $this> */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(FeePolicy::class, 'fee_policy_id');
    }

    /** @return BelongsTo<FeeItem, $this> */
    public function feeItem(): BelongsTo
    {
        return $this->belongsTo(FeeItem::class, 'fee_item_id');
    }
}
