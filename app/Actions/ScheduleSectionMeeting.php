<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\CourseSection;
use App\Models\Room;
use App\Models\SectionMeeting;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ScheduleSectionMeeting
{
    public function __invoke(
        CourseSection $section,
        User $actor,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        Carbon $validFrom,
        ?Carbon $validTo = null,
        ?Room $room = null,
        ?string $correlationId = null,
    ): SectionMeeting {
        if (! $actor->hasActiveRole(RoleCode::RegistrationOfficer)
            && ! $actor->hasActiveRole(RoleCode::FacilitiesOfficer)) {
            throw new AuthorizationException('جدولة اجتماعات الشعب مقصورة على التسجيل والمرافق.');
        }

        $meeting = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $section, $actor, $dayOfWeek, $startTime, $endTime,
            $validFrom, $validTo, $room, $correlationId, &$meeting,
        ): void {
            $meeting = SectionMeeting::create([
                'course_section_id' => $section->getKey(),
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room_id' => $room?->getKey(),
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'academic.section_meeting_scheduled',
                'subject_type' => SectionMeeting::class,
                'subject_id' => (string) $meeting->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'course_section_id' => $section->getKey(),
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'room_id' => $room?->getKey(),
                ],
            ]);
        });

        return $meeting->refresh();
    }
}
