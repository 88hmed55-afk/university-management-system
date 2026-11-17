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

final class CancelStudentInvoice
{
    /** Cancellation replaces deletion; separation of duties and a reason are required. */
    public function __invoke(
        StudentInvoice $invoice,
        User $actor,
        string $reason,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): StudentInvoice {
        if (! $actor->hasActiveRole(RoleCode::FinanceDirector)
            && ! $actor->hasActiveRole(RoleCode::FinancialLeadership)) {
            throw new AuthorizationException('إلغاء الفواتير مقصور على المالية والقيادة المالية.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('سبب إلغاء الفاتورة مطلوب.');
        }

        if ((int) $invoice->created_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لفاعل واحد إنشاء وإلغاء الفاتورة.');
        }

        if ($invoice->status === 'cancelled') {
            throw new InvalidArgumentException('الفاتورة ملغاة بالفعل.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($invoice, $actor, $reason, $decisionReference, $correlationId): void {
            $invoice->status = 'cancelled';
            $invoice->cancelled_at = now();
            $invoice->cancelled_by = $actor->getKey();
            $invoice->cancellation_reason = $reason;
            $invoice->decision_reference = $decisionReference;
            $invoice->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.invoice_cancelled',
                'subject_type' => StudentInvoice::class,
                'subject_id' => (string) $invoice->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'after' => [
                    'invoice_no' => $invoice->invoice_no,
                    'status' => 'cancelled',
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $invoice->refresh();
    }
}
