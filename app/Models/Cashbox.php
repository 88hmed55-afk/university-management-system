<?php

namespace App\Models;

use Database\Factories\CashboxFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Cashbox extends Model
{
    /** @use HasFactory<CashboxFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'campus_id', 'currency_id', 'status',
        'decision_reference', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (Cashbox $cashbox): void {
            if ($cashbox->isDirty('code')) {
                throw new LogicException('رمز الصندوق لا يُعدل؛ أنشئ صندوقاً جديداً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('الصناديق لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
