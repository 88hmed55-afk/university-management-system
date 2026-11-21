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

final class ApproveNotificationTemplate
{
    public function __invoke(
        NotificationTemplate $template,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): NotificationTemplate {
        if (! ServicesAccess::canPublish($actor)) {
            throw new AuthorizationException('اعتماد قوالب الإشعارات مقصور على الجهة المخولة.');
        }

        if ((int) $template->created_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لفاعل واحد إنشاء واعتماد القالب.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع اعتماد القالب مطلوب.');
        }

        if ($template->status !== 'draft') {
            throw new InvalidArgumentException('القالب ليس في حالة انتظار الاعتماد.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($template, $actor, $decisionReference, $correlationId): void {
            $template->status = 'approved';
            $template->approved_by = $actor->getKey();
            $template->approved_at = now();
            $template->decision_reference = $decisionReference;
            $template->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.notification_template_approved',
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
