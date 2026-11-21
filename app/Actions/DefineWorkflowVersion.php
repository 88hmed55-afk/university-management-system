<?php

namespace App\Actions;

use App\DataScopeCode;
use App\Models\AuditLog;
use App\Models\ServiceRequestType;
use App\Models\User;
use App\Models\WorkflowStep;
use App\RoleCode;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DefineWorkflowVersion
{
    /**
     * @param  array<int, array<string, mixed>>  $steps
     * @return Collection<int, WorkflowStep>
     */
    public function __invoke(
        ServiceRequestType $type,
        User $actor,
        int $versionNo,
        array $steps,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): Collection {
        if (! ServicesAccess::canConfigure($actor)) {
            throw new AuthorizationException('تعريف مسارات الطلبات مقصور على العمليات المخولة.');
        }

        if ($versionNo < 1 || $steps === []) {
            throw new InvalidArgumentException('رقم الإصدار وخطوات المسار مطلوبان.');
        }

        if (WorkflowStep::query()
            ->where('service_request_type_id', $type->getKey())
            ->where('version_no', $versionNo)
            ->exists()) {
            throw new InvalidArgumentException('إصدار المسار معرّف مسبقاً.');
        }

        $normalized = [];
        $expectedStep = 1;

        foreach ($steps as $step) {
            $stepNo = (int) ($step['step_no'] ?? 0);
            $name = trim((string) ($step['name'] ?? ''));
            $roleCode = RoleCode::tryFrom((string) ($step['role_code'] ?? ''));
            $scopeCode = DataScopeCode::tryFrom((string) ($step['scope_code'] ?? ''));

            if ($stepNo !== $expectedStep || $name === '' || $roleCode === null || $scopeCode === null) {
                throw new InvalidArgumentException('خطوات المسار يجب أن تكون متسلسلة وصالحة بالدور ونطاق البيانات.');
            }

            $normalized[] = [
                'step_no' => $stepNo,
                'name' => $name,
                'role_code' => $roleCode->value,
                'scope_code' => $scopeCode->value,
                'transition_condition' => $step['transition_condition'] ?? null,
            ];

            $expectedStep++;
        }

        $created = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $type, $actor, $versionNo, $normalized, $decisionReference, $correlationId, &$created,
        ): void {
            $created = new Collection;

            foreach ($normalized as $step) {
                $created->push(WorkflowStep::create([
                    'service_request_type_id' => $type->getKey(),
                    'version_no' => $versionNo,
                    'step_no' => $step['step_no'],
                    'name' => $step['name'],
                    'role_code' => $step['role_code'],
                    'scope_code' => $step['scope_code'],
                    'transition_condition' => $step['transition_condition'],
                    'status' => 'draft',
                    'decision_reference' => $decisionReference,
                ]));
            }

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.workflow_version_defined',
                'subject_type' => ServiceRequestType::class,
                'subject_id' => (string) $type->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['version_no' => $versionNo, 'steps' => count($normalized)],
            ]);
        });

        return $created;
    }
}
