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

final class PublishNotificationTemplate
{
    public function __invoke(
        NotificationTemplate $template,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): NotificationTemplate {
        if (! ServicesAccess::canPublish($actor)) {
            throw new AuthorizationException('نشر قوالب الإشعارات مقصور على الجهة المخولة.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع نشر القالب مطلوب.');
        }

        if ($template->status !== 'approved') {
            throw new InvalidArgumentException('يمكن نشر القوالب المعتمدة فقط.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($template, $actor, $decisionReference, $correlationId): void {
            $template->status = 'published';
            $template->decision_reference = $decisionReference;
            $template->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.notification_template_published',
                'subject_type' => NotificationTemplate::class,
                'subject_id' => (string) $template->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['code' => $template->code, 'version_no' => $template->version_no],
            ]);
        });

        return $template->refresh();
    }
}
