<?php

namespace App\Models;

use Database\Factories\StudentPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class StudentPayment extends Model
{
    /** @use HasFactory<StudentPaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_no', 'student_id', 'cashbox_id', 'cash_session_id', 'amount',
        'currency_id', 'method_code', 'external_reference', 'payment_date',
        'idempotency_key', 'received_by', 'status', 'decision_reference',
        'reversed_at', 'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (StudentPayment $payment): void {
            $immutable = [
                'payment_no', 'student_id', 'cashbox_id', 'cash_session_id',
                'amount', 'currency_id', 'method_code', 'external_reference',
                'payment_date', 'idempotency_key', 'received_by',
            ];

            if ($payment->isDirty($immutable)) {
                throw new LogicException('بيانات حركة القبض لا تُعدل؛ أنشأ عكساً موثّقاً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('حركات القبض لا تُحذف.');
        });
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<CashSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    /** @return HasOne<Receipt, $this> */
    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    /** @return HasMany<PaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'original_payment_id');
    }
}
