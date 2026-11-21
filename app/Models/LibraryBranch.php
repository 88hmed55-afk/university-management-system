<?php

namespace App\Models;

use Database\Factories\LibraryBranchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class LibraryBranch extends Model
{
    /** @use HasFactory<LibraryBranchFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'campus_id', 'status', 'decision_reference', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (LibraryBranch $branch): void {
            if ($branch->isDirty(['code', 'campus_id'])) {
                throw new LogicException('رمز فرع المكتبة وحرمه لا يُعدلان.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('فروع المكتبة لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<$this> $query */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $campusIds = $user->visibleCampusIds();

        if ($campusIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($campusIds !== null) {
            $query->whereIn('campus_id', $campusIds);
        }
    }

    /** @return BelongsTo<Campus, $this> */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
