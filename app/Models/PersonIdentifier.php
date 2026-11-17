<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\PersonIdentifierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PersonIdentifier extends Model
{
    /** @use HasFactory<PersonIdentifierFactory> */
    use HasFactory;

    protected $fillable = [
        'person_id',
        'identifier_type_id',
        'value',
        'value_hash',
        'issuing_authority',
        'effective_from',
        'effective_to',
        'status',
        'archived_at',
        'decision_reference',
    ];

    protected $hidden = [
        'value',
        'value_hash',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PersonIdentifier $identifier): void {
            if ($identifier->value !== null && $identifier->value !== '') {
                $identifier->value_hash = hash_hmac(
                    'sha256',
                    (string) $identifier->value,
                    (string) config('app.key'),
                );
            }
        });

        static::deleting(function (): never {
            throw new LogicException('المعرفات الرسمية لا تُحذف؛ تُغلق بفترة السريان أو الأرشفة.');
        });
    }

    /**
     * @param  Builder<PersonIdentifier>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /**
     * @param  Builder<PersonIdentifier>  $query
     */
    public function scopeCurrentlyValid(Builder $query): void
    {
        $query->active()
            ->where('effective_from', '<=', today())
            ->where(function (Builder $query): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', today());
            });
    }

    /**
     * @param  Builder<PersonIdentifier>  $query
     */
    public function scopeForPerson(Builder $query, int $personId): void
    {
        $query->where('person_id', $personId);
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return BelongsTo<PersonIdentifierType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(PersonIdentifierType::class, 'identifier_type_id');
    }
}
