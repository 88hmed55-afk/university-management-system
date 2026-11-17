<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'status', 'decision_reference', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (Currency $currency): void {
            if ($currency->isDirty('code')) {
                throw new LogicException('رمز العملة لا يُعدل؛ أنشئ عملة جديدة.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('العملات لا تُحذف؛ تُؤرشف.');
        });
    }
}
