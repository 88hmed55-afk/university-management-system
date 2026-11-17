<?php

namespace App\Models;

use Database\Factories\ProgramVersionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ProgramVersion extends Model
{
    /** @use HasFactory<ProgramVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_program_id', 'version_no', 'name', 'approved_at',
        'effective_from', 'effective_to', 'status', 'decision_reference',
        'approved_by', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ProgramVersion $version): void {
            if ($version->getOriginal('status') === 'approved'
                && $version->isDirty(['version_no', 'effective_from', 'effective_to', 'approved_at'])) {
                throw new LogicException('نسخة البرنامج المعتمدة لا تُعدل؛ أنشئ نسخة جديدة.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('نسخ البرامج لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<ProgramVersion> $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    /** @return BelongsTo<AcademicProgram, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'academic_program_id');
    }
}
