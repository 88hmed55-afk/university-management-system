<?php

namespace App\Models;

use App\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campus extends Model
{
    protected $fillable = [
        'institution_id',
        'code',
        'name',
        'address',
        'status',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * @param  Builder<Campus>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /**
     * @param  Builder<Campus>  $query
     */
    public function scopeForInstitution(Builder $query, int $institutionId): void
    {
        $query->where('institution_id', $institutionId);
    }
}
