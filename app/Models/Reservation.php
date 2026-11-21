<?php

namespace App\Models;

use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'reservation_no', 'bibliographic_record_id', 'beneficiary_type',
        'beneficiary_id', 'status', 'expires_at', 'idempotency_key',
        'acted_by', 'decision_reference',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (Reservation $reservation): void {
            if ($reservation->isDirty([
                'reservation_no', 'bibliographic_record_id',
                'beneficiary_type', 'beneficiary_id', 'idempotency_key',
            ])) {
                throw new LogicException('هوية الحجز لا تُعدل.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('الحجوزات لا تُحذف؛ تُلغى بإجراء موثّق.');
        });
    }

    /** @param Builder<$this> $query */
    public function scopeForBeneficiary(Builder $query, string $type, int $id): void
    {
        $query->where('beneficiary_type', $type)->where('beneficiary_id', $id);
    }

    public function beneficiary(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<BibliographicRecord, $this> */
    public function record(): BelongsTo
    {
        return $this->belongsTo(BibliographicRecord::class, 'bibliographic_record_id');
    }
}
