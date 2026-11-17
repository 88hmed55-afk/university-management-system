<?php

namespace App\Models;

use App\DataScopeCode;
use Database\Factories\DataScopeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataScope extends Model
{
    /** @use HasFactory<DataScopeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
    ];

    protected function casts(): array
    {
        return [
            'code' => DataScopeCode::class,
        ];
    }

    /**
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
