<?php

namespace App\Models;

use Database\Factories\PaymentAllocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PaymentAllocation extends Model
{
    /** @use HasFactory<PaymentAllocationFactory> */
    use HasFactory;

    protected $fillable = [
        'student_payment_id', 'student_invoice_id', 'amount',
        'created_by', 'decision_reference',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('تخصيصات الدفع غير قابلة للتعديل؛ أنشئ تخصيصاً تصحيحياً.');
        });

        static::deleting(function (): never {
            throw new LogicException('تخصيصات الدفع غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<StudentPayment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(StudentPayment::class, 'student_payment_id');
    }

    /** @return BelongsTo<StudentInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }
}
