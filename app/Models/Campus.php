<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\CampusFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Campus extends Model
{
    /** @use HasFactory<CampusFactory> */
    use HasFactory;

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

    /**
     * Server-side data scope: never fetch campus rows outside the user's scope.
     *
     * @param  Builder<Campus>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $campusIds = $user->visibleCampusIds();

        if ($campusIds === null) {
            return;
        }

        $query->whereIn('id', $campusIds);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_campus_assignments')
            ->withTimestamps();
    }
}
