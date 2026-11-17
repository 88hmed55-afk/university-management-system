<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\PublishedResult;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class WithdrawPublishedResult
{
    public function __invoke(
        PublishedResult $result,
        User $actor,
        string $reason,
        ?string $correlationId = null,
    ): PublishedResult {
        if (! $actor->hasActiveRole(RoleCode::RegistrationOfficer)
            && ! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('سحب النشر مقصور على التسجيل والقيادة الأكاديمية.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('سبب سحب النشر مطلوب.');
        }

        if ($result->withdrawn_at !== null) {
            throw new InvalidArgumentException('النتيجة مسحوبة بالفعل.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($result, $actor, $reason, $correlationId): void {
            $result->withdrawn_at = now();
            $result->withdrawn_by = $actor->getKey();
            $result->withdrawal_reason = $reason;
            $result->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'grades.publish_withdrawn',
                'subject_type' => PublishedResult::class,
                'subject_id' => (string) $result->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
            ]);
        });

        return $result->refresh();
    }
}
