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

final class ActivateScheduledAnnouncement
{
    public function __invoke(
        Announcement $announcement,
        User $actor,
        ?string $correlationId = null,
    ): Announcement {
        if (! ServicesAccess::canPublish($actor)) {
            throw new AuthorizationException('تفعيل الإعلانات المجدولة مقصور على الجهة المخولة.');
        }

        if ($announcement->status !== 'scheduled') {
            throw new InvalidArgumentException('الإعلان ليس مجدولاً.');
        }

        if ($announcement->publish_at === null || $announcement->publish_at->isFuture()) {
            throw new InvalidArgumentException('لم يحن موعد نشر الإعلان بعد.');
        }

        if ($announcement->expires_at !== null && $announcement->expires_at->isPast()) {
            throw new InvalidArgumentException('انتهت صلاحية الإعلان المجدول.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($announcement, $actor, $correlationId): void {
            $announcement->status = 'published';
            $announcement->published_at = now();
            $announcement->published_by = $actor->getKey();
            $announcement->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.announcement_activated',
                'subject_type' => Announcement::class,
                'subject_id' => (string) $announcement->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['announcement_no' => $announcement->announcement_no, 'status' => 'published'],
            ]);
        });

        return $announcement->refresh();
    }
}
