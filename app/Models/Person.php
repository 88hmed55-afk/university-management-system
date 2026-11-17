<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    protected $fillable = [
        'given_name',
        'family_name',
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
            throw new LogicException('لا تُحذف الأشخاص؛ تُؤرشف مع بقاء السجل.');
        });
    }

    /**
     * @param  Builder<Person>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim($this->given_name.' '.$this->family_name),
        );
    }

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * @return HasMany<PersonIdentifier, $this>
     */
    public function identifiers(): HasMany
    {
        return $this->hasMany(PersonIdentifier::class);
    }
}
