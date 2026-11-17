<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\FloorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    /** @use HasFactory<FloorFactory> */
    use HasFactory;

    protected $fillable = [
        'building_id',
        'code',
        'name',
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

    /**
     * @param  Builder<Floor>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /**
     * @param  Builder<Floor>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $campusIds = $user->visibleCampusIds();

        if ($campusIds === null) {
            return;
        }

        $query->whereHas('building', function (Builder $query) use ($campusIds): void {
            $query->whereIn('campus_id', $campusIds);
        });
    }

    /**
     * @return BelongsTo<Building, $this>
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
