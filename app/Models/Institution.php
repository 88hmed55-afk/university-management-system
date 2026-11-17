<?php

namespace App\Models;

use App\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = [
        'code',
        'name',
        'legal_name',
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

    public function settings(): HasMany
    {
        return $this->hasMany(InstitutionSetting::class);
    }

    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class);
    }
}
