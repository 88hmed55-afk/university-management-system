<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\DeliveryAttempt;
use App\Models\Notification;
use App\Models\User;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RecordDeliveryAttempt
{
    /** Append-only retry-safe log. External providers stay locked until P-021 is approved. */
    public function __invoke(
        Notification $notification,
        User $actor,
        int $attemptNo,
        string $status,
        ?string $error = null,
        ?string $providerCode = null,
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
    ): DeliveryAttempt {
        if (! ServicesAccess::canSendNotifications($actor)) {
            throw new AuthorizationException('تسجيل محاولات الإرسال مقصور على الجهات المخولة.');
        }

        if ($attemptNo < 1 || ! in_array($status, ['success', 'failed'], true)) {
            throw new InvalidArgumentException('رقم المحاولة أو نتيجتها غير صالح.');
        }

        if ($providerCode !== null) {
            throw new InvalidArgumentException('لا تُسجَّل محاولات عبر مزود خارجي قبل اعتماده (P-021).');
        }

        if ($status === 'failed' && trim((string) $error) === '') {
            throw new InvalidArgumentException('سبب فشل الإرسال مطلوب.');
        }

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $existing = DeliveryAttempt::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing->refresh();
            }
        }

        if (DeliveryAttempt::query()
            ->where('notification_id', $notification->getKey())
            ->where('attempt_no', $attemptNo)
            ->exists()) {
            throw new InvalidArgumentException('رقم محاولة الإرسال مستخدم مسبقاً لهذا الإشعار.');
        }

        $attempt = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $notification, $actor, $attemptNo, $status, $error,
            $idempotencyKey, $correlationId, &$attempt,
        ): void {
            $attempt = DeliveryAttempt::create([
                'notification_id' => $notification->getKey(),
                'attempt_no' => $attemptNo,
                'channel_code' => $notification->channel_code,
                'status' => $status,
                'error' => $error,
                'idempotency_key' => $idempotencyKey,
                'correlation_id' => $correlationId,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.delivery_attempt_recorded',
                'subject_type' => DeliveryAttempt::class,
                'subject_id' => (string) $attempt->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $error,
                'after' => [
                    'notification_no' => $notification->notification_no,
                    'attempt_no' => $attemptNo,
                    'status' => $status,
                ],
            ]);
        });

        return $attempt->refresh();
    }
}
