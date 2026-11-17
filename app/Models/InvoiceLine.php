<?php

namespace App\Models;

use Database\Factories\InvoiceLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class InvoiceLine extends Model
{
    /** @use HasFactory<InvoiceLineFactory> */
    use HasFactory;

    protected $fillable = [
        'student_invoice_id', 'fee_item_id', 'description', 'amount', 'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceLine $line): void {
            if ($line->exists && $line->invoice->status !== 'draft') {
                throw new LogicException('بنود الفاتورة المُصدرة لا تُعدل.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('بنود الفاتورة لا تُحذف.');
        });
    }

    /** @return BelongsTo<StudentInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    /** @return BelongsTo<FeeItem, $this> */
    public function feeItem(): BelongsTo
    {
        return $this->belongsTo(FeeItem::class, 'fee_item_id');
    }
}
