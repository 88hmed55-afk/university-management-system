<?php

namespace App\Models;

use Database\Factories\ItemCopyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ItemCopy extends Model
{
    /** @use HasFactory<ItemCopyFactory> */
    use HasFactory;

    protected $fillable = [
        'copy_no', 'bibliographic_record_id', 'library_branch_id',
        'location_code', 'status', 'acquired_at', 'decision_reference',
    ];

    protected function casts(): array
    {
        return ['acquired_at' => 'date'];
    }

    protected static function booted(): void
    {
        static::updating(function (ItemCopy $copy): void {
            if ($copy->isDirty(['copy_no', 'bibliographic_record_id', 'library_branch_id'])) {
                throw new LogicException('هوية النسخة لا تُعدل.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('نسخ المكتبة لا تُحذف؛ تُسجَّل كتالفة أو مفقودة.');
        });
    }

    /** @return BelongsTo<BibliographicRecord, $this> */
    public function record(): BelongsTo
    {
        return $this->belongsTo(BibliographicRecord::class, 'bibliographic_record_id');
    }

    /** @return BelongsTo<LibraryBranch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(LibraryBranch::class, 'library_branch_id');
    }
}
