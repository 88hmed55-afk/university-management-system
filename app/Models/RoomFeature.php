<?php

namespace App\Models;

use Database\Factories\RoomFeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoomFeature extends Model
{
    /** @use HasFactory<RoomFeatureFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
    ];

    /**
     * @return BelongsToMany<Room, $this>
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_feature_assignments')
            ->withPivot(['effective_from', 'effective_to', 'decision_reference'])
            ->withTimestamps();
    }
}
