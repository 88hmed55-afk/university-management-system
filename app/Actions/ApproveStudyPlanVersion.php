<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\StudyPlanVersion;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ApproveStudyPlanVersion
{
    public function __invoke(
        StudyPlanVersion $version,
        User $actor,
        string $decisionReference,
        ?Carbon $effectiveFrom = null,
        ?string $correlationId = null,
    ): StudyPlanVersion {
        if (! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('اعتماد خطط الدراسة مقصور على القيادة الأكاديمية المخولين.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع اعتماد الخطة مطلوب.');
        }

        if ($version->status === 'approved') {
            throw new InvalidArgumentException('الخطة معتمدة بالفعل؛ أنشئ نسخة جديدة.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $version, $actor, $decisionReference, $effectiveFrom, $correlationId,
        ): void {
            $version->status = 'approved';
            $version->approved_at = now();
            $version->approved_by = $actor->getKey();
            $version->decision_reference = $decisionReference;
            $version->effective_from = $effectiveFrom;
            $version->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'academic.study_plan_approved',
                'subject_type' => StudyPlanVersion::class,
                'subject_id' => (string) $version->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'version_no' => $version->version_no,
                    'decision_reference' => $decisionReference,
                    'effective_from' => $effectiveFrom?->toDateString(),
                ],
            ]);
        });

        return $version->refresh();
    }
}
