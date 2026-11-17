<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use LogicException;

class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'start_date', 'end_date', 'status',
        'approved_by', 'decision_reference', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AcademicYear $year): void {
            if ($year->end_date->lessThanOrEqualTo($year->start_date)) {
                throw new InvalidArgumentException('تاريخ نهاية السنة الأكاديمية يجب أن يكون بعد بدايتها.');
            }

            $overlaps = static::query()
                ->when($year->exists, fn (Builder $query) => $query->whereKeyNot($year->getKey()))
                ->where('start_date', '<=', $year->end_date->toDateString())
                ->where('end_date', '>=', $year->start_date->toDateString())
                ->exists();

            if ($overlaps) {
                throw new InvalidArgumentException('لا يجوز تداخل السنوات الأكاديمية.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('السنوات الأكاديمية لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<AcademicYear> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /** @return HasMany<AcademicTerm, $this> */
    public function terms(): HasMany
    {
        return $this->hasMany(AcademicTerm::class);
    }
}
