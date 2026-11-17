<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\ProgramCollegeParticipationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class ProgramCollegeParticipation extends Model
{
    /** @use HasFactory<ProgramCollegeParticipationFactory> */
    use HasFactory;

    protected $fillable = [
        'academic_program_id', 'college_id', 'participation_type_code',
        'valid_from', 'valid_to', 'decision_reference', 'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProgramCollegeParticipation $participation): void {
            if ($participation->valid_to !== null
                && $participation->valid_to->lessThanOrEqualTo($participation->valid_from)) {
                throw new InvalidArgumentException('تاريخ نهاية المشاركة يجب أن يكون بعد بدايتها.');
            }

            $primaryCollegeId = (int) $participation->program->primary_college_id;

            if ((int) $participation->college_id === $primaryCollegeId) {
                throw new InvalidArgumentException('الكلية الرئيسية لا تُسجَّل ككلية مشاركة.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('مشاركات البرنامج لا تُحذف؛ تُغلق بتاريخ النهاية.');
        });
    }

    /** @param Builder<ProgramCollegeParticipation> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /** @param Builder<ProgramCollegeParticipation> $query */
    public function scopeCurrentlyValid(Builder $query): void
    {
        $query->active()
            ->where('valid_from', '<=', today())
            ->where(function (Builder $query): void {
                $query->whereNull('valid_to')->orWhere('valid_to', '>', today());
            });
    }

    /** @return BelongsTo<AcademicProgram, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'academic_program_id');
    }

    /** @return BelongsTo<College, $this> */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }
}
