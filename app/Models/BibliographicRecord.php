<?php

namespace App\Models;

use Database\Factories\BibliographicRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class BibliographicRecord extends Model
{
    /** @use HasFactory<BibliographicRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'record_no', 'title', 'author', 'classification_code', 'isbn',
        'language_code', 'publisher', 'published_year', 'library_branch_id',
        'status', 'decision_reference',
    ];

    protected static function booted(): void
    {
        static::updating(function (BibliographicRecord $record): void {
            if ($record->isDirty(['record_no', 'library_branch_id', 'isbn'])) {
                throw new LogicException('رقم السجل وفرعه وISBN لا تُعدل؛ أنشئ سجلاً جديداً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('سجلات الفهرس لا تُحذف.');
        });
    }

    /** @return BelongsTo<LibraryBranch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(LibraryBranch::class, 'library_branch_id');
    }

    /** @return HasMany<ItemCopy, $this> */
    public function copies(): HasMany
    {
        return $this->hasMany(ItemCopy::class);
    }
}
