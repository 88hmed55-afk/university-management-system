<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\FinancialAdjustment;
use App\Models\Refund;
use App\Models\StudentPayment;
use App\Models\User;
use App\Support\FinanceAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ExecuteRefund
{
    /** Executing a refund creates a reversal adjustment linked to the original movement. */
    public function __invoke(
        Refund $refund,
        User $actor,
        string $adjustmentNo,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): FinancialAdjustment {
        if (! FinanceAccess::canApprove($actor)) {
            throw new AuthorizationException('تنفيذ الرد مقصور على المالية المخولة.');
        }

        if (trim($adjustmentNo) === '') {
            throw new InvalidArgumentException('رقم التسوية مطلوب.');
        }

        if ($refund->status !== 'approved') {
            throw new InvalidArgumentException('يمكن تنفيذ الردود المعتمدة فقط.');
        }

        $payment = StudentPayment::findOrFail($refund->original_payment_id);

        if ($payment->status === 'reversed') {
            throw new InvalidArgumentException('الدفعة الأصلية معكوسة بالفعل.');
        }

        $adjustment = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $refund, $payment, $actor, $adjustmentNo, $decisionReference, $correlationId, &$adjustment,
        ): void {
            $adjustment = FinancialAdjustment::create([
                'adjustment_no' => $adjustmentNo,
                'student_id' => $payment->student_id,
                'original_payment_id' => $payment->getKey(),
                'direction' => 'credit',
                'amount' => $refund->amount,
                'reason' => $refund->reason,
                'created_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            $refund->status = 'executed';
            $refund->executed_at = now();
            $refund->decision_reference = $decisionReference;
            $refund->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.refund_executed',
                'subject_type' => Refund::class,
                'subject_id' => (string) $refund->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'refund_no' => $refund->refund_no,
                    'adjustment_no' => $adjustmentNo,
                    'original_payment_id' => $payment->getKey(),
                ],
            ]);
        });

        return $adjustment->refresh();
    }
}
