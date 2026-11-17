<?php

namespace App\Actions;

use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\CourseSection;
use App\Models\User;
use App\Support\SectionAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OpenAttendanceSession
{
    public function __invoke(
        CourseSection $section,
        User $actor,
        Carbon $sessionDate,
        ?string $notes = null,
        ?string $correlationId = null,
    ): AttendanceSession {
        if (! SectionAccess::canRecordOn($actor, $section)) {
            throw new AuthorizationException('تسجيل الحضور مقصور على مدرس الشعبة أو التسجيل/القيادة الأكاديمية.');
        }

        $session = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $section, $actor, $sessionDate, $notes, $correlationId, &$session,
        ): void {
            $session = AttendanceSession::create([
                'course_section_id' => $section->getKey(),
                'session_date' => $sessionDate,
                'status' => 'open',
                'opened_by' => $actor->getKey(),
                'notes' => $notes,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'attendance.session_opened',
                'subject_type' => AttendanceSession::class,
                'subject_id' => (string) $session->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'course_section_id' => $section->getKey(),
                    'session_date' => $sessionDate->toDateString(),
                ],
            ]);
        });

        return $session->refresh();
    }
}
