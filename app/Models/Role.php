<?php

namespace App\Models;

use App\RoleCode;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'data_scope_id',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'code' => RoleCode::class,
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DataScope, $this>
     */
    public function dataScope(): BelongsTo
    {
        return $this->belongsTo(DataScope::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['granted_by', 'valid_from', 'valid_to', 'reason'])
            ->withTimestamps();
    }
}
