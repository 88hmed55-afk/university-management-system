<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Refund;
use App\Models\User;
use App\Support\FinanceAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ApproveRefund
{
    /** Separation of duties: approver must differ from requester (docs/04 SCR-075). */
    public function __invoke(
        Refund $refund,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): Refund {
        if (! FinanceAccess::canApprove($actor)) {
            throw new AuthorizationException('اعتماد الرد مقصور على المالية المخولة.');
        }

        if ((int) $refund->requested_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لفاعل واحد طلب واعتماد الرد.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار الاعتماد مطلوب.');
        }

        if ($refund->status !== 'requested') {
            throw new InvalidArgumentException('الرد ليس في حالة انتظار الاعتماد.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($refund, $actor, $decisionReference, $correlationId): void {
            $refund->status = 'approved';
            $refund->approved_by = $actor->getKey();
            $refund->approved_at = now();
            $refund->decision_reference = $decisionReference;
            $refund->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.refund_approved',
                'subject_type' => Refund::class,
                'subject_id' => (string) $refund->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'refund_no' => $refund->refund_no,
                    'status' => 'approved',
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $refund->refresh();
    }
}
