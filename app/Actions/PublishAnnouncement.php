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

final class PublishAnnouncement
{
    /** Publisher must differ from the drafter; a future publish_at results in a scheduled state. */
    public function __invoke(
        Announcement $announcement,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): Announcement {
        if (! ServicesAccess::canPublish($actor)) {
            throw new AuthorizationException('نشر الإعلانات مقصور على الجهة المخولة.');
        }

        if ((int) $announcement->created_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لفاعل واحد إنشاء ونشر الإعلان.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار النشر مطلوب.');
        }

        if ($announcement->status !== 'draft') {
            throw new InvalidArgumentException('الإعلان ليس في حالة مسودة.');
        }

        $status = $announcement->publish_at !== null && $announcement->publish_at->isFuture()
            ? 'scheduled'
            : 'published';

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $announcement, $actor, $decisionReference, $status, $correlationId,
        ): void {
            $announcement->status = $status;
            $announcement->published_by = $actor->getKey();
            $announcement->published_at = now();
            $announcement->decision_reference = $decisionReference;
            $announcement->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.announcement_published',
                'subject_type' => Announcement::class,
                'subject_id' => (string) $announcement->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'announcement_no' => $announcement->announcement_no,
                    'status' => $status,
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $announcement->refresh();
    }
}
