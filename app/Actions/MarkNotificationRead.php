<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class MarkNotificationRead
{
    /** Only the recipient may mark their own notification as read (SCR-006 no leaking). */
    public function __invoke(Notification $notification, User $actor, ?string $correlationId = null): Notification
    {
        if ((int) $notification->recipient_id !== (int) $actor->getKey()) {
            throw new AuthorizationException('يمكن للمستلم فقط تحديد إشعاره كمقروء.');
        }

        if ($notification->status === 'read') {
            throw new InvalidArgumentException('الإشعار مقروء بالفعل.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($notification, $actor, $correlationId): void {
            $notification->status = 'read';
            $notification->read_at = now();
            $notification->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.notification_read',
                'subject_type' => Notification::class,
                'subject_id' => (string) $notification->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['notification_no' => $notification->notification_no, 'status' => 'read'],
            ]);
        });

        return $notification->refresh();
    }
}
