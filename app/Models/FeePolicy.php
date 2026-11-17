<?php

namespace App\Models;

use Database\Factories\FeePolicyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class FeePolicy extends Model
{
    /** @use HasFactory<FeePolicyFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'version_no', 'name', 'academic_program_id', 'currency_id',
        'effective_from', 'effective_to', 'status', 'decision_reference',
        'created_by', 'approved_by', 'approved_at', 'notes', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (FeePolicy $policy): void {
            if ($policy->getOriginal('status') === 'draft') {
                return;
            }

            $immutable = [
                'code', 'version_no', 'name', 'academic_program_id',
                'currency_id', 'effective_from', 'effective_to', 'created_by',
            ];

            if ($policy->isDirty($immutable)) {
                throw new LogicException('إصدار الرسوم المعتمد/المنشور لا يُعدل؛ أنشئ إصداراً جديداً.');
            }

            if ($policy->getOriginal('status') === 'published') {
                throw new LogicException('إصدار الرسوم المنشور لا يُعدل؛ أنشئ إصداراً جديداً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('سياسات الرسوم لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<FeePolicy> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /** @return BelongsTo<AcademicProgram, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'academic_program_id');
    }

    /** @return HasMany<FeePolicyItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(FeePolicyItem::class);
    }
}
