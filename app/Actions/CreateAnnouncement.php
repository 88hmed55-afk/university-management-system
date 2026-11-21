<?php

namespace App\Actions;

use App\DataScopeCode;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CreateAnnouncement
{
    /** Announcements carry no subject record link, so they cannot leak entity details by construction. */
    public function __invoke(
        User $actor,
        string $announcementNo,
        string $title,
        string $body,
        string $locale,
        string $audienceScopeCode,
        ?string $scopeKey = null,
        ?string $publishAt = null,
        ?string $expiresAt = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): Announcement {
        if (! ServicesAccess::canConfigure($actor)) {
            throw new AuthorizationException('إنشاء الإعلانات مقصور على العمليات المخولة.');
        }

        if (trim($announcementNo) === '' || trim($title) === '' || trim($body) === '') {
            throw new InvalidArgumentException('رقم الإعلان وعنوانه ومحتواه مطلوبة.');
        }

        if (! in_array($locale, ['ar', 'en'], true)) {
            throw new InvalidArgumentException('لغة الإعلان غير مدعومة.');
        }

        $scope = DataScopeCode::tryFrom($audienceScopeCode);

        if ($scope === null) {
            throw new InvalidArgumentException('نطاق جمهور الإعلان غير معروف.');
        }

        if ($scope === DataScopeCode::Institution && $scopeKey !== null) {
            throw new InvalidArgumentException('الإعلان المؤسسي لا يحتاج مفتاح نطاق.');
        }

        if ($scope !== DataScopeCode::Institution && trim((string) $scopeKey) === '') {
            throw new InvalidArgumentException('مفتاح النطاق مطلوب للجمهور المحدد.');
        }

        $publish = $publishAt !== null ? Carbon::parse($publishAt) : null;
        $expires = $expiresAt !== null ? Carbon::parse($expiresAt) : null;

        if ($publish !== null && $expires !== null && $expires->lt($publish)) {
            throw new InvalidArgumentException('تاريخ انتهاء الإعلان لا يمكن أن يسبق تاريخ نشره.');
        }

        $announcement = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $announcementNo, $title, $body, $locale, $audienceScopeCode,
            $scopeKey, $publish, $expires, $decisionReference, $correlationId, &$announcement,
        ): void {
            $announcement = Announcement::create([
                'announcement_no' => $announcementNo,
                'title' => $title,
                'body' => $body,
                'locale' => $locale,
                'audience_scope_code' => $audienceScopeCode,
                'scope_key' => $scopeKey,
                'status' => 'draft',
                'publish_at' => $publish,
                'expires_at' => $expires,
                'created_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.announcement_created',
                'subject_type' => Announcement::class,
                'subject_id' => (string) $announcement->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'announcement_no' => $announcementNo,
                    'audience_scope_code' => $audienceScopeCode,
                    'scope_key' => $scopeKey,
                ],
            ]);
        });

        return $announcement->refresh();
    }
}
