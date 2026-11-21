<?php

namespace App\Models;

use Database\Factories\CashSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class CashSession extends Model
{
    /** @use HasFactory<CashSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'cashbox_id', 'opened_by', 'opened_at', 'closed_by', 'closed_at',
        'expected_amount', 'actual_amount', 'difference_reason', 'status',
        'approved_by', 'approved_at', 'decision_reference',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'approved_at' => 'datetime',
            'expected_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (CashSession $session): void {
            if ($session->isDirty(['cashbox_id', 'opened_by', 'opened_at'])) {
                throw new LogicException('هوية الوردية لا تُعدل.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('ورديات الصندوق لا تُحذف.');
        });
    }

    /** @return BelongsTo<Cashbox, $this> */
    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    /** @return HasMany<StudentPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(StudentPayment::class);
    }
}
