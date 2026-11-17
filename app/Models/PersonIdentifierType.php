<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\PersonIdentifierTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class PersonIdentifierType extends Model
{
    /** @use HasFactory<PersonIdentifierTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
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

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('أنواع المعرفات المرجعية لا تُحذف؛ تُؤرشف.');
        });
    }

    /**
     * @param  Builder<PersonIdentifierType>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    public function label(): string
    {
        $key = 'identifier_types.'.$this->code;

        return trans()->has($key) ? trans($key) : (string) $this->code;
    }

    /**
     * @return HasMany<PersonIdentifier, $this>
     */
    public function identifiers(): HasMany
    {
        return $this->hasMany(PersonIdentifier::class);
    }
}
