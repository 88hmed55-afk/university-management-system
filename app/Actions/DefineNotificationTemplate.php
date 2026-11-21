<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DefineNotificationTemplate
{
    public function __invoke(
        User $actor,
        string $code,
        int $versionNo,
        string $channelCode,
        string $subject,
        string $body,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): NotificationTemplate {
        if (! ServicesAccess::canConfigure($actor)) {
            throw new AuthorizationException('تعريف قوالب الإشعارات مقصور على العمليات المخولة.');
        }

        if (trim($code) === '' || trim($channelCode) === '' || trim($subject) === '' || trim($body) === '') {
            throw new InvalidArgumentException('رمز القالب وقناته وعنوانه ومحتواه مطلوبة.');
        }

        if ($versionNo < 1) {
            throw new InvalidArgumentException('رقم إصدار القالب يجب أن يكون موجباً.');
        }

        if (NotificationTemplate::query()
            ->where('code', $code)
            ->where('version_no', $versionNo)
            ->exists()) {
            throw new InvalidArgumentException('إصدار القالب معرّف مسبقاً.');
        }

        $template = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $code, $versionNo, $channelCode, $subject, $body,
            $decisionReference, $correlationId, &$template,
        ): void {
            $template = NotificationTemplate::create([
                'code' => $code,
                'version_no' => $versionNo,
                'channel_code' => $channelCode,
                'subject' => $subject,
                'body' => $body,
                'status' => 'draft',
                'created_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.notification_template_defined',
                'subject_type' => NotificationTemplate::class,
                'subject_id' => (string) $template->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['code' => $code, 'version_no' => $versionNo, 'channel_code' => $channelCode],
            ]);
        });

        return $template->refresh();
    }
}
