<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestType;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PublishWorkflowVersion
{
    /** Publishing a new version while requests are in flight requires a documented migration reference. */
    public function __invoke(
        ServiceRequestType $type,
        User $actor,
        int $versionNo,
        string $decisionReference,
        ?string $migrationReference = null,
        ?string $correlationId = null,
    ): Collection {
        if (! ServicesAccess::canConfigure($actor)) {
            throw new AuthorizationException('نشر مسارات الطلبات مقصور على العمليات المخولة.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار نشر المسار مطلوب.');
        }

        $steps = WorkflowStep::query()
            ->where('service_request_type_id', $type->getKey())
            ->where('version_no', $versionNo)
            ->orderBy('step_no')
            ->get();

        if ($steps->isEmpty()) {
            throw new InvalidArgumentException('إصدار المسار غير معرّف.');
        }

        $currentVersion = $type->current_workflow_version_no;

        if ($currentVersion !== null && (int) $currentVersion !== $versionNo) {
            $inFlight = ServiceRequest::query()
                ->where('service_request_type_id', $type->getKey())
                ->where('workflow_version_no', $currentVersion)
                ->whereNotIn('status', ['completed', 'rejected', 'withdrawn'])
                ->exists();

            if ($inFlight && trim((string) $migrationReference) === '') {
                throw new InvalidArgumentException('تغيير المسار مع طلبات جارية يتطلب مرجع ترحيل موثّقاً.');
            }
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $type, $actor, $versionNo, $steps, $decisionReference,
            $migrationReference, $correlationId,
        ): void {
            foreach ($steps as $step) {
                $step->status = 'published';
                $step->decision_reference = $decisionReference;
                $step->save();
            }

            $type->current_workflow_version_no = $versionNo;
            $type->decision_reference = $migrationReference ?: $decisionReference;
            $type->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.workflow_version_published',
                'subject_type' => ServiceRequestType::class,
                'subject_id' => (string) $type->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $migrationReference,
                'after' => ['version_no' => $versionNo, 'decision_reference' => $decisionReference],
            ]);
        });

        return $steps->fresh();
    }
}
