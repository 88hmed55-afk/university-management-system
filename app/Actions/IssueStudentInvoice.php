<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\StudentInvoice;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class IssueStudentInvoice
{
    public function __invoke(
        StudentInvoice $invoice,
        User $actor,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): StudentInvoice {
        if (! $actor->hasActiveRole(RoleCode::FinanceDirector)
            && ! $actor->hasActiveRole(RoleCode::FinancialLeadership)) {
            throw new AuthorizationException('إصدار الفواتير مقصور على المالية والقيادة المالية.');
        }

        if ($invoice->status !== 'draft') {
            throw new InvalidArgumentException('يمكن إصدار الفواتير المسودة فقط.');
        }

        if ($invoice->policy->status !== 'published') {
            throw new InvalidArgumentException('سياسة الرسوم المرتبطة غير منشورة.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($invoice, $actor, $decisionReference, $correlationId): void {
            $invoice->status = 'issued';
            $invoice->issued_at = now();
            $invoice->issued_by = $actor->getKey();
            $invoice->decision_reference = $decisionReference;
            $invoice->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.invoice_issued',
                'subject_type' => StudentInvoice::class,
                'subject_id' => (string) $invoice->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'invoice_no' => $invoice->invoice_no,
                    'status' => 'issued',
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $invoice->refresh();
    }
}
