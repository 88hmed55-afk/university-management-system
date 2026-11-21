<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\RequestAudit;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class WithdrawServiceRequest
{
    /** Withdrawal is only possible before processing and is always documented, never a delete. */
    public function __invoke(
        ServiceRequest $request,
        User $actor,
        string $reason,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): ServiceRequest {
        $isRequester = $request->requester_type === $actor->getMorphClass()
            && (int) $request->requester_id === (int) $actor->getKey();

        if (! $isRequester && ! ServicesAccess::canManageRequests($actor)) {
            throw new AuthorizationException('سحب الطلب مقصور على مقدمه أو خدمات الطلاب.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('سبب السحب مطلوب.');
        }

        if (! in_array($request->status, ['submitted', 'returned'], true)) {
            throw new InvalidArgumentException('لا يمكن سحب طلب بدأت معالجته.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $request, $actor, $reason, $decisionReference, $correlationId,
        ): void {
            $request->status = 'withdrawn';
            $request->closed_at = now();
            $request->decision_reference = $decisionReference;
            $request->save();

            RequestAudit::event($request, $actor, 'withdrawn', $reason, $correlationId);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.request_withdrawn',
                'subject_type' => ServiceRequest::class,
                'subject_id' => (string) $request->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'after' => ['request_no' => $request->request_no, 'status' => 'withdrawn'],
            ]);
        });

        return $request->refresh();
    }
}
