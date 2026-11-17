<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\AdmissionCycleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class AdmissionCycle extends Model
{
    /** @use HasFactory<AdmissionCycleFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'application_open_at', 'application_close_at', 'decision_at',
        'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'application_open_at' => 'datetime',
            'application_close_at' => 'datetime',
            'decision_at' => 'datetime',
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('دورات القبول لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<AdmissionCycle> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
