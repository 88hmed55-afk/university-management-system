<?php

namespace App\Actions;

use App\DataScopeCode;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SendNotification
{
    /** Internal channel only until a provider is approved (P-021); one recipient per notification. */
    public function __invoke(
        User $actor,
        User $recipient,
        string $notificationNo,
        ?NotificationTemplate $template = null,
        ?string $subject = null,
        ?string $body = null,
        ?string $audienceScopeCode = null,
        ?string $actionUrl = null,
        ?string $idempotencyKey = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): Notification {
        if (! ServicesAccess::canSendNotifications($actor)) {
            throw new AuthorizationException('إرسال الإشعارات مقصور على الجهات المخولة.');
        }

        if (! $recipient->hasAnyActiveRole()) {
            throw new InvalidArgumentException('المستلم ليس لديه دور نشط؛ لا يُرسل إشعار لمستخدم غير مخول.');
        }

        if ($template !== null) {
            if ($template->status !== 'published') {
                throw new InvalidArgumentException('لا يمكن الإرسال إلا بقالب منشور.');
            }

            if ($subject !== null || $body !== null) {
                throw new InvalidArgumentException('عنوان الإشعار ومحتواه يُؤخذان من القالب المنشور.');
            }

            $subject = $template->subject;
            $body = $template->body;
            $channelCode = $template->channel_code;
        } else {
            if (trim((string) $subject) === '' || trim((string) $body) === '') {
                throw new InvalidArgumentException('عنوان الإشعار ومحتواه مطلوبان.');
            }

            $channelCode = 'internal';
        }

        if ($channelCode !== 'internal') {
            throw new InvalidArgumentException('القنوات الخارجية مقفلة إلى حين اعتماد مزود موثوق (P-021).');
        }

        if ($audienceScopeCode !== null && DataScopeCode::tryFrom($audienceScopeCode) === null) {
            throw new InvalidArgumentException('نطاق جمهور الإشعار غير معروف.');
        }

        if ($audienceScopeCode === DataScopeCode::Self->value
            && (int) $recipient->getKey() !== (int) $actor->getKey()) {
            throw new AuthorizationException('إشعار النطاق الذاتي يُرسل لصاحبه فقط.');
        }

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $existing = Notification::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing->refresh();
            }
        }

        $notification = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $recipient, $notificationNo, $template, $subject, $body,
            $channelCode, $audienceScopeCode, $actionUrl, $idempotencyKey,
            $decisionReference, $correlationId, &$notification,
        ): void {
            $notification = Notification::create([
                'notification_no' => $notificationNo,
                'recipient_id' => $recipient->getKey(),
                'notification_template_id' => $template?->getKey(),
                'channel_code' => $channelCode,
                'subject' => $subject,
                'body' => $body,
                'audience_scope_code' => $audienceScopeCode,
                'action_url' => $actionUrl,
                'status' => 'unread',
                'idempotency_key' => $idempotencyKey,
                'correlation_id' => $correlationId,
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.notification_sent',
                'subject_type' => Notification::class,
                'subject_id' => (string) $notification->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'notification_no' => $notificationNo,
                    'recipient_id' => $recipient->getKey(),
                    'channel_code' => $channelCode,
                ],
            ]);
        });

        return $notification->refresh();
    }
}
