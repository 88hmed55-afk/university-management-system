<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestType;
use App\Models\User;
use App\Support\RequestAudit;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SubmitServiceRequest
{
    public function __invoke(
        ServiceRequestType $type,
        Model $requester,
        User $actor,
        string $description,
        string $requestNo,
        ?string $idempotencyKey = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): ServiceRequest {
        $isRequester = get_class($requester) === get_class($actor) && $requester->getKey() === $actor->getKey();

        if (! $isRequester && ! ServicesAccess::canManageRequests($actor)) {
            throw new AuthorizationException('لا يمكن تقديم طلب نيابة عن غيرك دون صلاحية خدمات الطلاب.');
        }

        if (trim($description) === '' || trim($requestNo) === '') {
            throw new InvalidArgumentException('وصف الطلب ورقمه مطلوبان.');
        }

        if ($type->status !== 'active') {
            throw new InvalidArgumentException('نوع الطلب غير فعّال.');
        }

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $existing = ServiceRequest::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing->refresh();
            }
        }

        $versionNo = $type->current_workflow_version_no;
        $request = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $type, $requester, $actor, $description, $requestNo, $idempotencyKey,
            $decisionReference, $correlationId, $versionNo, &$request,
        ): void {
            $request = ServiceRequest::create([
                'request_no' => $requestNo,
                'service_request_type_id' => $type->getKey(),
                'requester_type' => $requester->getMorphClass(),
                'requester_id' => $requester->getKey(),
                'description' => $description,
                'status' => 'submitted',
                'workflow_version_no' => $versionNo,
                'current_step_no' => $versionNo !== null ? 1 : null,
                'channel_code' => 'internal',
                'idempotency_key' => $idempotencyKey,
                'decision_reference' => $decisionReference,
                'submitted_at' => now(),
            ]);

            RequestAudit::event($request, $actor, 'submitted', null, $correlationId);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.request_submitted',
                'subject_type' => ServiceRequest::class,
                'subject_id' => (string) $request->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['request_no' => $requestNo, 'type_id' => $type->getKey()],
            ]);
        });

        return $request->refresh();
    }
}
