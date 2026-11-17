<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;
use LogicException;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'document_type',
        'storage_key',
        'original_name',
        'mime_type',
        'size_bytes',
        'checksum',
        'uploaded_by',
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
        static::saving(function (Document $document): void {
            if (str_starts_with($document->storage_key, 'public/')) {
                throw new InvalidArgumentException('مفتاح التخزين يجب أن يشير إلى تخزين خاص غير عام.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('لا تُحذف الوثائق؛ تُؤرشف مع بقاء البيانات الوصفية.');
        });
    }

    /**
     * @param  Builder<Document>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /**
     * @param  Builder<Document>  $query
     */
    public function scopeForOwner(Builder $query, string $ownerType, int $ownerId): void
    {
        $query->where('owner_type', $ownerType)->where('owner_id', $ownerId);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasMany<DocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function currentVersion(): ?DocumentVersion
    {
        return $this->versions()->orderByDesc('version_no')->first();
    }
}
