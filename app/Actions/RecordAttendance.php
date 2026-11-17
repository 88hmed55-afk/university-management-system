<?php

namespace App\Actions;

use App\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Support\SectionAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RecordAttendance
{
    public function __invoke(
        AttendanceSession $session,
        CourseEnrollment $enrollment,
        AttendanceStatus $status,
        User $actor,
        ?string $excuseReason = null,
        ?string $correlationId = null,
    ): AttendanceRecord {
        if (! SectionAccess::canRecordOn($actor, $session->section)) {
            throw new AuthorizationException('تسجيل الحضور مقصور على مدرس الشعبة أو التسجيل/القيادة الأكاديمية.');
        }

        $record = null;
        $before = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $session, $enrollment, $status, $actor, $excuseReason, $correlationId, &$record, &$before,
        ): void {
            $record = AttendanceRecord::query()
                ->where('attendance_session_id', $session->getKey())
                ->where('course_enrollment_id', $enrollment->getKey())
                ->first();

            if ($record === null) {
                $record = AttendanceRecord::create([
                    'attendance_session_id' => $session->getKey(),
                    'course_enrollment_id' => $enrollment->getKey(),
                    'status' => $status,
                    'excuse_reason' => $excuseReason,
                    'recorded_by' => $actor->getKey(),
                    'recorded_at' => now(),
                ]);
            } else {
                $before = [
                    'status' => $record->status->value,
                    'excuse_reason' => $record->excuse_reason,
                ];
                $record->status = $status;
                $record->excuse_reason = $excuseReason;
                $record->recorded_by = $actor->getKey();
                $record->recorded_at = now();
                $record->save();
            }

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'attendance.record_saved',
                'subject_type' => AttendanceRecord::class,
                'subject_id' => (string) $record->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'before' => $before,
                'after' => [
                    'attendance_session_id' => $session->getKey(),
                    'course_enrollment_id' => $enrollment->getKey(),
                    'status' => $status->value,
                    'excuse_reason' => $excuseReason,
                ],
            ]);
        });

        return $record->refresh();
    }
}
