<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\FeePolicy;
use App\Models\InvoiceLine;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\User;
use App\RoleCode;
use App\Support\MoneyMath;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CreateStudentInvoice
{
    /**
     * Create a draft invoice from a published fee policy, snapshotting amounts
     * into lines. Invoice numbers are unique and never reused (docs/06/docs/04 SCR-073).
     */
    public function __invoke(
        Student $student,
        FeePolicy $policy,
        User $actor,
        string $invoiceNo,
        ?int $academicTermId = null,
        ?string $idempotencyKey = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): StudentInvoice {
        if (! $actor->hasActiveRole(RoleCode::FinanceDirector)
            && ! $actor->hasActiveRole(RoleCode::FinancialLeadership)) {
            throw new AuthorizationException('إنشاء الفواتير مقصور على المالية والقيادة المالية.');
        }

        if (trim($invoiceNo) === '') {
            throw new InvalidArgumentException('رقم الفاتورة مطلوب.');
        }

        if ($policy->status !== 'published') {
            throw new InvalidArgumentException('لا يمكن الفوترة إلا من سياسة رسوم منشورة.');
        }

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $existing = StudentInvoice::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing->refresh();
            }
        }

        $duplicateQuery = StudentInvoice::query()
            ->where('student_id', $student->getKey())
            ->where('fee_policy_id', $policy->getKey())
            ->whereIn('status', ['draft', 'issued']);

        if ($academicTermId !== null) {
            $duplicateQuery->where('academic_term_id', $academicTermId);
        } else {
            $duplicateQuery->whereNull('academic_term_id');
        }

        if ($duplicateQuery->exists()) {
            throw new InvalidArgumentException('يوجد استحقاق مفتوح لهذا الطالب من السياسة نفسها.');
        }

        $policy->loadMissing('items.feeItem');

        $total = '0.00';
        $lineData = [];

        foreach ($policy->items as $item) {
            $lineTotal = MoneyMath::subtract(
                (string) $item->amount,
                $item->discount_amount !== null ? (string) $item->discount_amount : '0',
            );
            $total = MoneyMath::add($total, $lineTotal);
            $lineData[] = [
                'fee_item_id' => $item->fee_item_id,
                'description' => $item->feeItem?->name,
                'amount' => (string) $item->amount,
                'discount_amount' => $item->discount_amount !== null ? (string) $item->discount_amount : null,
            ];
        }

        if ($lineData === []) {
            throw new InvalidArgumentException('سياسة الرسوم لا تحتاج بنوداً للفوترة.');
        }

        $invoice = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $student, $policy, $actor, $invoiceNo, $academicTermId, $idempotencyKey,
            $decisionReference, $total, $lineData, $correlationId, &$invoice,
        ): void {
            $invoice = StudentInvoice::create([
                'invoice_no' => $invoiceNo,
                'student_id' => $student->getKey(),
                'fee_policy_id' => $policy->getKey(),
                'currency_id' => $policy->currency_id,
                'academic_term_id' => $academicTermId,
                'status' => 'draft',
                'total_amount' => $total,
                'idempotency_key' => $idempotencyKey,
                'decision_reference' => $decisionReference,
                'created_by' => $actor->getKey(),
            ]);

            foreach ($lineData as $line) {
                InvoiceLine::create(array_merge(['student_invoice_id' => $invoice->getKey()], $line));
            }

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.invoice_created',
                'subject_type' => StudentInvoice::class,
                'subject_id' => (string) $invoice->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'invoice_no' => $invoiceNo,
                    'student_id' => $student->getKey(),
                    'fee_policy_id' => $policy->getKey(),
                    'currency_id' => $policy->currency_id,
                    'total_amount' => $total,
                ],
            ]);
        });

        return $invoice->refresh();
    }
}
