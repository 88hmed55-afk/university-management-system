<?php

namespace App\Models;

use Database\Factories\AttendanceSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class AttendanceSession extends Model
{
    /** @use HasFactory<AttendanceSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'course_section_id', 'session_date', 'status', 'opened_by',
        'approved_by', 'approved_at', 'notes', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (AttendanceSession $session): void {
            if ($session->getOriginal('status') === 'approved'
                && $session->isDirty(['course_section_id', 'session_date'])) {
                throw new LogicException('جلسة الحضور المعتمدة لا تُعدل؛ أنشئ جلسة تصحيح.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('جلسات الحضور لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<AttendanceSession> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', 'open');
    }

    /** @return BelongsTo<CourseSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
