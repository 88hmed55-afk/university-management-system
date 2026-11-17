<?php

namespace App\Models;

use Database\Factories\FeeItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class FeeItem extends Model
{
    /** @use HasFactory<FeeItemFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'description', 'status', 'decision_reference', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (FeeItem $item): void {
            if ($item->isDirty('code')) {
                throw new LogicException('رمز بند الرسوم لا يُعدل؛ أنشئ بنداً جديداً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('بنود الرسوم لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<FeeItem> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }
}
