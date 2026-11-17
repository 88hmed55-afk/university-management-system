<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected $fillable = [
        'floor_id',
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
     * @param  Builder<Room>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /**
     * @param  Builder<Room>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $campusIds = $user->visibleCampusIds();

        if ($campusIds === null) {
            return;
        }

        $query->whereHas('floor.building', function (Builder $query) use ($campusIds): void {
            $query->whereIn('campus_id', $campusIds);
        });
    }

    /**
     * @return BelongsTo<Floor, $this>
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * @return HasMany<RoomCapacityPeriod, $this>
     */
    public function capacityPeriods(): HasMany
    {
        return $this->hasMany(RoomCapacityPeriod::class);
    }

    /**
     * @return BelongsToMany<RoomFeature, $this>
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(RoomFeature::class, 'room_feature_assignments')
            ->withPivot(['effective_from', 'effective_to', 'decision_reference'])
            ->withTimestamps();
    }

    public function currentCapacity(): ?int
    {
        $capacity = $this->capacityPeriods()
            ->where('effective_from', '<=', today())
            ->where(function (Builder $query): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', today());
            })
            ->value('capacity');

        return $capacity === null ? null : (int) $capacity;
    }
}
