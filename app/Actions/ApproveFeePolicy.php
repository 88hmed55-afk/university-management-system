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

final class ApproveFeePolicy
{
    /** Separation of duties: approver must differ from creator (docs/04 SCR-071). */
    public function __invoke(
        FeePolicy $policy,
        User $actor,
        string $decisionReference,
        ?string $notes = null,
        ?string $correlationId = null,
    ): FeePolicy {
        if (! $actor->hasActiveRole(RoleCode::FinanceDirector)
            && ! $actor->hasActiveRole(RoleCode::FinancialLeadership)) {
            throw new AuthorizationException('اعتماد سياسات الرسوم مقصور على المالية والقيادة المالية.');
        }

        if ((int) $policy->created_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لفاعل واحد إنشاء واعتماد سياسة الرسوم.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار الاعتماد مطلوب.');
        }

        if ($policy->status !== 'draft') {
            throw new InvalidArgumentException('السياسة ليست في حالة انتظار الاعتماد.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($policy, $actor, $decisionReference, $notes, $correlationId): void {
            $policy->status = 'approved';
            $policy->approved_by = $actor->getKey();
            $policy->approved_at = now();
            $policy->decision_reference = $decisionReference;
            $policy->notes = $notes;
            $policy->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.fee_policy_approved',
                'subject_type' => FeePolicy::class,
                'subject_id' => (string) $policy->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'status' => 'approved',
                    'decision_reference' => $decisionReference,
                    'created_by' => $policy->created_by,
                ],
            ]);
        });

        return $policy->refresh();
    }
}
