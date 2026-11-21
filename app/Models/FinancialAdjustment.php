<?php

namespace App\Models;

use Database\Factories\FinancialAdjustmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class FinancialAdjustment extends Model
{
    /** @use HasFactory<FinancialAdjustmentFactory> */
    use HasFactory;

    protected $fillable = [
        'adjustment_no', 'student_id', 'original_payment_id', 'original_invoice_id',
        'direction', 'amount', 'reason', 'created_by', 'decision_reference',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('التسويات المالية غير قابلة للتعديل.');
        });

        static::deleting(function (): never {
            throw new LogicException('التسويات المالية غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
