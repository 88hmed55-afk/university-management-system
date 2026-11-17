<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\AcademicTermFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class AcademicTerm extends Model
{
    /** @use HasFactory<AcademicTermFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_year_id', 'code', 'name', 'term_type_code',
        'start_date', 'end_date', 'registration_open_at', 'registration_close_at',
        'exam_start_at', 'exam_end_at', 'status', 'approved_by',
        'decision_reference', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_open_at' => 'date',
            'registration_close_at' => 'date',
            'exam_start_at' => 'date',
            'exam_end_at' => 'date',
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AcademicTerm $term): void {
            if ($term->end_date->lessThanOrEqualTo($term->start_date)) {
                throw new InvalidArgumentException('تاريخ نهاية الفصل يجب أن يكون بعد بدايته.');
            }

            if ($term->registration_close_at !== null
                && $term->registration_open_at !== null
                && $term->registration_close_at->lessThan($term->registration_open_at)) {
                throw new InvalidArgumentException('تاريخ إغلاق التسجيل يجب أن يكون بعد فتحه.');
            }

            $overlaps = static::query()
                ->where('academic_year_id', $term->academic_year_id)
                ->when($term->exists, fn (Builder $query) => $query->whereKeyNot($term->getKey()))
                ->where('start_date', '<=', $term->end_date->toDateString())
                ->where('end_date', '>=', $term->start_date->toDateString())
                ->exists();

            if ($overlaps) {
                throw new InvalidArgumentException('لا يجوز تداخل الفصول في السنة الأكاديمية نفسها.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('الفصول الأكاديمية لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<AcademicTerm> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
