<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Receipt;
use App\Models\StudentPayment;
use App\Models\User;
use App\Support\FinanceAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class IssueReceipt
{
    /** Immutable receipt snapshot; one per payment, idempotent on repeat. */
    public function __invoke(
        StudentPayment $payment,
        User $actor,
        string $receiptNo,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): Receipt {
        if (! FinanceAccess::canCapture($actor)) {
            throw new AuthorizationException('إصدار الإيصالات مقصور على أمين الصندوق والمالية.');
        }

        if (trim($receiptNo) === '') {
            throw new InvalidArgumentException('رقم الإيصال مطلوب.');
        }

        $existing = Receipt::query()->where('student_payment_id', $payment->getKey())->first();

        if ($existing !== null) {
            return $existing->refresh();
        }

        $receipt = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $payment, $actor, $receiptNo, $decisionReference, $correlationId, &$receipt,
        ): void {
            $receipt = Receipt::create([
                'receipt_no' => $receiptNo,
                'student_payment_id' => $payment->getKey(),
                'payload' => [
                    'payment_no' => $payment->payment_no,
                    'student_id' => $payment->student_id,
                    'amount' => $payment->amount,
                    'currency_id' => $payment->currency_id,
                    'method_code' => $payment->method_code,
                    'payment_date' => $payment->payment_date?->toIso8601String(),
                ],
                'issued_at' => now(),
                'issued_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.receipt_issued',
                'subject_type' => Receipt::class,
                'subject_id' => (string) $receipt->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['receipt_no' => $receiptNo, 'payment_no' => $payment->payment_no],
            ]);
        });

        return $receipt->refresh();
    }
}
