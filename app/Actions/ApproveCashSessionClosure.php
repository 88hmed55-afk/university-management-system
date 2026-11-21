<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\CashSession;
use App\Models\User;
use App\Support\FinanceAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ApproveCashSessionClosure
{
    /** Financial supervisor approves a closed session; approver must differ from the cashier. */
    public function __invoke(
        CashSession $session,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): CashSession {
        if (! FinanceAccess::canApprove($actor)) {
            throw new AuthorizationException('اعتماد إقفال الوردية مقصور على المشرف المالي.');
        }

        if ((int) $session->closed_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لأمين الصندوق اعتماد إقفال ورديته.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار الاعتماد مطلوب.');
        }

        if ($session->status !== 'closed' || $session->approved_at !== null) {
            throw new InvalidArgumentException('الوردية ليست في حالة انتظار الاعتماد.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($session, $actor, $decisionReference, $correlationId): void {
            $session->approved_by = $actor->getKey();
            $session->approved_at = now();
            $session->decision_reference = $decisionReference;
            $session->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'cash.session_closure_approved',
                'subject_type' => CashSession::class,
                'subject_id' => (string) $session->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'status' => 'closed',
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $session->refresh();
    }
}
