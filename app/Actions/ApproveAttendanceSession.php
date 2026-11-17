<?php

namespace App\Actions;

use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\SectionAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ApproveAttendanceSession
{
    public function __invoke(
        AttendanceSession $session,
        User $actor,
        ?string $correlationId = null,
    ): AttendanceSession {
        if (! SectionAccess::canRecordOn($actor, $session->section)) {
            throw new AuthorizationException('اعتماد الحضور مقصور على مدرس الشعبة أو التسجيل/القيادة الأكاديمية.');
        }

        if ($session->status === 'approved') {
            throw new InvalidArgumentException('الجلسة معتمدة بالفعل.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($session, $actor, $correlationId): void {
            $session->status = 'approved';
            $session->approved_by = $actor->getKey();
            $session->approved_at = now();
            $session->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'attendance.session_approved',
                'subject_type' => AttendanceSession::class,
                'subject_id' => (string) $session->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'status' => 'approved',
                    'session_date' => $session->session_date->toDateString(),
                ],
            ]);
        });

        return $session->refresh();
    }
}
