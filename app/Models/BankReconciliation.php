<?php

namespace App\Models;

use Database\Factories\BankReconciliationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class BankReconciliation extends Model
{
    /** @use HasFactory<BankReconciliationFactory> */
    use HasFactory;

    protected $fillable = [
        'cashbox_id', 'period_start', 'period_end', 'expected_amount',
        'actual_amount', 'difference_amount', 'notes', 'status',
        'prepared_by', 'reviewed_by', 'reviewed_at', 'decision_reference',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'expected_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('مطابقات البنك غير قابلة للتعديل؛ أنشئ مطابقة جديدة.');
        });

        static::deleting(function (): never {
            throw new LogicException('مطابقات البنك غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<Cashbox, $this> */
    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }
}
