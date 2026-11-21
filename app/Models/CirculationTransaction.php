<?php

namespace App\Models;

use Database\Factories\CirculationTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class CirculationTransaction extends Model
{
    /** @use HasFactory<CirculationTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'transaction_no', 'item_copy_id', 'beneficiary_type', 'beneficiary_id',
        'transaction_type', 'original_transaction_id', 'due_at', 'occurred_at',
        'condition_note', 'acted_by', 'status', 'decision_reference', 'correlation_id',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'occurred_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('حركات التداول غير قابلة للتعديل؛ سجّل حركة جديدة.');
        });

        static::deleting(function (): never {
            throw new LogicException('حركات التداول غير قابلة للحذف.');
        });
    }

    /** @param Builder<$this> $query */
    public function scopeForBeneficiary(Builder $query, string $type, int $id): void
    {
        $query->where('beneficiary_type', $type)->where('beneficiary_id', $id);
    }

    public function beneficiary(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<ItemCopy, $this> */
    public function copy(): BelongsTo
    {
        return $this->belongsTo(ItemCopy::class, 'item_copy_id');
    }
}
