<?php

namespace App\Models;

use Database\Factories\ReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Receipt extends Model
{
    /** @use HasFactory<ReceiptFactory> */
    use HasFactory;

    protected $fillable = [
        'receipt_no', 'student_payment_id', 'payload', 'issued_at',
        'issued_by', 'decision_reference',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('الإيصالات غير قابلة للتعديل.');
        });

        static::deleting(function (): never {
            throw new LogicException('الإيصالات غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<StudentPayment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(StudentPayment::class, 'student_payment_id');
    }
}
