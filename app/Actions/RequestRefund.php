<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Refund;
use App\Models\StudentPayment;
use App\Models\User;
use App\Support\FinanceAccess;
use App\Support\MoneyMath;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RequestRefund
{
    /** A refund always links to the original movement; amount is bounded by what was captured. */
    public function __invoke(
        StudentPayment $payment,
        User $actor,
        string $amount,
        string $reason,
        string $refundNo,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): Refund {
        if (! FinanceAccess::canApprove($actor)) {
            throw new AuthorizationException('طلب الرد مقصور على المالية المخولة.');
        }

        if (trim($reason) === '' || trim($refundNo) === '') {
            throw new InvalidArgumentException('سبب الرد ورقمه مطلوبان.');
        }

        $amount = MoneyMath::nonNegative($amount, 'المبلغ');

        if ($amount === null || bccomp($amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('مبلغ الرد يجب أن يكون أكبر من صفر.');
        }

        $alreadyRefunded = (string) $payment->refunds()
            ->whereIn('status', ['approved', 'executed'])
            ->sum('amount');
        $remaining = MoneyMath::subtract((string) $payment->amount, $alreadyRefunded);

        if (bccomp($amount, $remaining, 2) > 0) {
            throw new InvalidArgumentException('مبلغ الرد يتجاوز المتاح من الدفعة الأصلية.');
        }

        $refund = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $payment, $actor, $amount, $reason, $refundNo,
            $decisionReference, $correlationId, &$refund,
        ): void {
            $refund = Refund::create([
                'refund_no' => $refundNo,
                'original_payment_id' => $payment->getKey(),
                'amount' => $amount,
                'reason' => $reason,
                'requested_by' => $actor->getKey(),
                'status' => 'requested',
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.refund_requested',
                'subject_type' => Refund::class,
                'subject_id' => (string) $refund->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'after' => [
                    'refund_no' => $refundNo,
                    'original_payment_id' => $payment->getKey(),
                    'amount' => $amount,
                ],
            ]);
        });

        return $refund->refresh();
    }
}
