<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\AcademicProgramFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class AcademicProgram extends Model
{
    /** @use HasFactory<AcademicProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'level_code', 'primary_college_id', 'status', 'archived_at',
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
            throw new LogicException('البرامج الأكاديمية لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<AcademicProgram> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /** @return BelongsTo<College, $this> */
    public function primaryCollege(): BelongsTo
    {
        return $this->belongsTo(College::class, 'primary_college_id');
    }

    /** @return HasMany<ProgramCollegeParticipation, $this> */
    public function participations(): HasMany
    {
        return $this->hasMany(ProgramCollegeParticipation::class);
    }

    /** @return HasMany<ProgramVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ProgramVersion::class);
    }
}
