<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\PaymentAllocation;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use App\Models\User;
use App\Support\FinanceAccess;
use App\Support\MoneyMath;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AllocatePaymentToInvoice
{
    /**
     * Allocate part of a captured payment to an outstanding issued invoice.
     * No over-allocation beyond the payment remaining or the invoice outstanding.
     */
    public function __invoke(
        StudentPayment $payment,
        StudentInvoice $invoice,
        User $actor,
        string $amount,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): PaymentAllocation {
        if (! FinanceAccess::canCapture($actor)) {
            throw new AuthorizationException('تخصيص الدفعات مقصور على أمين الصندوق والمالية.');
        }

        $amount = MoneyMath::nonNegative($amount, 'المبلغ');

        if ($amount === null || bccomp($amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('مبلغ التخصيص يجب أن يكون أكبر من صفر.');
        }

        if ((int) $payment->student_id !== (int) $invoice->student_id) {
            throw new InvalidArgumentException('الدفعة والفاتورة لطالبين مختلفين.');
        }

        if ($payment->status !== 'captured') {
            throw new InvalidArgumentException('الدفعة غير نشطة للتخصيص.');
        }

        if ($invoice->status !== 'issued') {
            throw new InvalidArgumentException('الفاتورة غير مُصدرة.');
        }

        if ((int) $payment->currency_id !== (int) $invoice->currency_id) {
            throw new InvalidArgumentException('عملة الدفعة لا تطابق عملة الفاتورة.');
        }

        $allocated = (string) $payment->allocations()->sum('amount');
        $paymentRemaining = MoneyMath::subtract((string) $payment->amount, $allocated);

        if (bccomp($amount, $paymentRemaining, 2) > 0) {
            throw new InvalidArgumentException('المبلغ يتجاوز المتبقي من الدفعة.');
        }

        $invoiceAllocated = (string) PaymentAllocation::query()
            ->where('student_invoice_id', $invoice->getKey())
            ->sum('amount');
        $invoiceOutstanding = MoneyMath::subtract((string) $invoice->total_amount, $invoiceAllocated);

        if (bccomp($amount, $invoiceOutstanding, 2) > 0) {
            throw new InvalidArgumentException('المبلغ يتجاوز المتبقي من الفاتورة.');
        }

        $allocation = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $payment, $invoice, $actor, $amount, $decisionReference, $correlationId, &$allocation,
        ): void {
            $allocation = PaymentAllocation::create([
                'student_payment_id' => $payment->getKey(),
                'student_invoice_id' => $invoice->getKey(),
                'amount' => $amount,
                'created_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.payment_allocated',
                'subject_type' => PaymentAllocation::class,
                'subject_id' => (string) $allocation->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'payment_no' => $payment->payment_no,
                    'invoice_no' => $invoice->invoice_no,
                    'amount' => $amount,
                ],
            ]);
        });

        return $allocation->refresh();
    }
}
