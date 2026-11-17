<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\FeePolicy;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PublishFeePolicy
{
    /**
     * Publishing marks an approved policy as available for invoicing.
     * Effective dating is stored; no retroactive application is performed (docs/04 SCR-071).
     */
    public function __invoke(
        FeePolicy $policy,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): FeePolicy {
        if (! $actor->hasActiveRole(RoleCode::FinanceDirector)
            && ! $actor->hasActiveRole(RoleCode::FinancialLeadership)) {
            throw new AuthorizationException('نشر سياسات الرسوم مقصور على المالية والقيادة المالية.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار النشر مطلوب.');
        }

        if ($policy->status !== 'approved') {
            throw new InvalidArgumentException('لا يمكن النشر قبل اعتماد السياسة.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($policy, $actor, $decisionReference, $correlationId): void {
            $policy->status = 'published';
            $policy->decision_reference = $decisionReference;
            $policy->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.fee_policy_published',
                'subject_type' => FeePolicy::class,
                'subject_id' => (string) $policy->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'status' => 'published',
                    'decision_reference' => $decisionReference,
                    'effective_from' => $policy->effective_from->toDateString(),
                ],
            ]);
        });

        return $policy->refresh();
    }
}
