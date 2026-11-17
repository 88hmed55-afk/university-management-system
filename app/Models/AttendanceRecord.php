<?php

namespace App\Models;

use App\AttendanceStatus;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'attendance_session_id', 'course_enrollment_id', 'status', 'excuse_reason',
        'recorded_by', 'recorded_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'recorded_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AttendanceRecord $record): void {
            $session = $record->session;

            if ($session->status === 'approved') {
                throw new LogicException('لا يمكن تعديل سجلات جلسة حضور معتمدة.');
            }

            $enrollment = $record->enrollment;

            if ((int) $enrollment->course_section_id !== (int) $session->course_section_id) {
                throw new InvalidArgumentException('الطالب غير مسجل في شعبة هذه الجلسة.');
            }

            if ($record->status === AttendanceStatus::Excused && trim((string) $record->excuse_reason) === '') {
                throw new InvalidArgumentException('سبب العذر مطلوب.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('سجلات الحضور لا تُحذف؛ تُعدَّل بإجراء موثّق.');
        });
    }

    /** @return BelongsTo<AttendanceSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    /** @return BelongsTo<CourseEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }
}
