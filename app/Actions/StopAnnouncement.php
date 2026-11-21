<?php

namespace App\Actions;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class StopAnnouncement
{
    /** Stopping is a documented exception; the announcement is never deleted or edited. */
    public function __invoke(
        Announcement $announcement,
        User $actor,
        string $reason,
        string $decisionReference,
        ?string $correlationId = null,
    ): Announcement {
        if (! ServicesAccess::canPublish($actor)) {
            throw new AuthorizationException('إيقاف الإعلانات مقصور على الجهة المخولة.');
        }

        if (trim($reason) === '' || trim($decisionReference) === '') {
            throw new InvalidArgumentException('سبب الإيقاف ومرجع القرار مطلوبان.');
        }

        if (! in_array($announcement->status, ['published', 'scheduled'], true)) {
            throw new InvalidArgumentException('الإعلان ليس في حالة تسمح بالإيقاف.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $announcement, $actor, $reason, $decisionReference, $correlationId,
        ): void {
            $announcement->status = 'stopped';
            $announcement->decision_reference = $decisionReference;
            $announcement->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.announcement_stopped',
                'subject_type' => Announcement::class,
                'subject_id' => (string) $announcement->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'after' => [
                    'announcement_no' => $announcement->announcement_no,
                    'status' => 'stopped',
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $announcement->refresh();
    }
}
