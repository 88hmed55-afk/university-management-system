<?php

namespace App\Models;

use Database\Factories\DocumentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class DocumentVersion extends Model
{
    /** @use HasFactory<DocumentVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version_no',
        'storage_key',
        'original_name',
        'mime_type',
        'size_bytes',
        'checksum',
        'created_by',
        'decision_reference',
    ];

    protected static function booted(): void
    {
        static::saving(function (DocumentVersion $version): void {
            if (str_starts_with($version->storage_key, 'public/')) {
                throw new InvalidArgumentException('مفتاح التخزين يجب أن يشير إلى تخزين خاص غير عام.');
            }
        });

        static::updating(function (): never {
            throw new LogicException('نسخ الوثائق غير قابلة للتعديل؛ أضف نسخة جديدة.');
        });

        static::deleting(function (): never {
            throw new LogicException('نسخ الوثائق غير قابلة للحذف.');
        });
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
