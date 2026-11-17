<?php

namespace App\Models;

use Database\Factories\StudentInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class StudentInvoice extends Model
{
    /** @use HasFactory<StudentInvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_no', 'student_id', 'fee_policy_id', 'currency_id', 'academic_term_id',
        'status', 'total_amount', 'idempotency_key', 'decision_reference', 'created_by',
        'issued_at', 'issued_by', 'cancelled_at', 'cancelled_by', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (StudentInvoice $invoice): void {
            $immutable = [
                'invoice_no', 'student_id', 'fee_policy_id', 'currency_id',
                'academic_term_id', 'total_amount', 'idempotency_key',
            ];

            if ($invoice->isDirty($immutable)) {
                throw new LogicException('الهوية والمبلغ في الفاتورة لا تُعدل؛ أنشئ فاتورة جديدة أو عكساً موثّقاً.');
            }

            if ($invoice->getOriginal('status') === 'cancelled'
                && $invoice->isDirty(['status', 'cancelled_at', 'cancellation_reason'])) {
                throw new LogicException('الفاتورة الملغاة لا تُعدل.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('الفواتير لا تُحذف؛ تُلغى بإجراء موثّق.');
        });
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<FeePolicy, $this> */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(FeePolicy::class, 'fee_policy_id');
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @return HasMany<InvoiceLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
