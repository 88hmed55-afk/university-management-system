<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Cashbox;
use App\Models\CashSession;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\User;
use App\Support\FinanceAccess;
use App\Support\MoneyMath;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CaptureStudentPayment
{
    /**
     * Capture a payment with idempotency and unique external reference.
     * Receipt printing is a separate action — failure there never cancels capture (docs/04 SCR-074).
     */
    public function __invoke(
        Student $student,
        User $actor,
        string $amount,
        int $currencyId,
        string $methodCode,
        string $paymentNo,
        ?string $externalReference = null,
        ?Cashbox $cashbox = null,
        ?CashSession $session = null,
        ?string $idempotencyKey = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): StudentPayment {
        if (! FinanceAccess::canCapture($actor)) {
            throw new AuthorizationException('تحصيل الدفعات مقصور على أمين الصندوق والمالية.');
        }

        $amount = MoneyMath::nonNegative($amount, 'المبلغ');

        if ($amount === null || bccomp($amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('مبلغ الدفعة يجب أن يكون أكبر من صفر.');
        }

        if (trim($methodCode) === '' || trim($paymentNo) === '') {
            throw new InvalidArgumentException('طريقة الدفع ورقم الدفعة مطلوبان.');
        }

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $existing = StudentPayment::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing->refresh();
            }
        }

        if ($session !== null) {
            if ($session->status !== 'open') {
                throw new InvalidArgumentException('الوردية غير مفتوحة.');
            }

            if ($cashbox === null || (int) $session->cashbox_id !== (int) $cashbox->getKey()) {
                throw new InvalidArgumentException('الوردية لا تنتمي إلى الصندوق المحدد.');
            }
        }

        $payment = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $student, $actor, $amount, $currencyId, $methodCode, $paymentNo,
            $externalReference, $cashbox, $session, $idempotencyKey,
            $decisionReference, $correlationId, &$payment,
        ): void {
            $payment = StudentPayment::create([
                'payment_no' => $paymentNo,
                'student_id' => $student->getKey(),
                'cashbox_id' => $cashbox?->getKey(),
                'cash_session_id' => $session?->getKey(),
                'amount' => $amount,
                'currency_id' => $currencyId,
                'method_code' => $methodCode,
                'external_reference' => $externalReference,
                'payment_date' => now(),
                'idempotency_key' => $idempotencyKey,
                'received_by' => $actor->getKey(),
                'status' => 'captured',
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.payment_captured',
                'subject_type' => StudentPayment::class,
                'subject_id' => (string) $payment->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'payment_no' => $paymentNo,
                    'student_id' => $student->getKey(),
                    'amount' => $amount,
                    'currency_id' => $currencyId,
                    'external_reference' => $externalReference,
                ],
            ]);
        });

        return $payment->refresh();
    }
}
