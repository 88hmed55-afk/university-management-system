<?php

namespace App\Actions;

use App\Models\AdmissionDecision;
use App\Models\ApplicantEvent;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DecideApplication
{
    public function __invoke(
        Application $application,
        string $decision,
        User $actor,
        string $reference,
        ?string $reason = null,
        ?string $correlationId = null,
    ): AdmissionDecision {
        if (! $actor->hasActiveRole(RoleCode::AdmissionsRegistrationDirector)) {
            throw new AuthorizationException('قرارات القبول مقصورة على إدارة القبول والتسجيل المخولين.');
        }

        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('قرار القبول غير معروف.');
        }

        if (trim($reference) === '') {
            throw new InvalidArgumentException('مرجع قرار القبول مطلوب.');
        }

        $correlationId ??= Str::uuid()->toString();
        $decisionRow = null;

        DB::transaction(function () use (
            $application, $decision, $actor, $reference, $reason, $correlationId, &$decisionRow,
        ): void {
            $decisionRow = AdmissionDecision::create([
                'application_id' => $application->getKey(),
                'decision' => $decision,
                'decided_by' => $actor->getKey(),
                'decided_at' => now(),
                'reference' => $reference,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            ApplicantEvent::create([
                'application_id' => $application->getKey(),
                'event_type' => 'decided',
                'actor_id' => $actor->getKey(),
                'correlation_id' => $correlationId,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'admission.decision_recorded',
                'subject_type' => Application::class,
                'subject_id' => (string) $application->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'after' => ['decision' => $decision, 'reference' => $reference],
            ]);
        });

        return $decisionRow->refresh();
    }
}
