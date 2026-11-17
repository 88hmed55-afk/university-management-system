<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'admission_cycle_id', 'person_id', 'application_no', 'program_code',
        'idempotency_key', 'status', 'submitted_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'submitted_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('طلبات القبول لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<Application> $query */
    public function scopeForCycle(Builder $query, int $cycleId): void
    {
        $query->where('admission_cycle_id', $cycleId);
    }

    /** @return BelongsTo<AdmissionCycle, $this> */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AdmissionCycle::class, 'admission_cycle_id');
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return HasMany<AdmissionReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(AdmissionReview::class);
    }

    /** @return HasMany<AdmissionDecision, $this> */
    public function decisions(): HasMany
    {
        return $this->hasMany(AdmissionDecision::class);
    }

    /** @return HasMany<ApplicantEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ApplicantEvent::class);
    }
}
