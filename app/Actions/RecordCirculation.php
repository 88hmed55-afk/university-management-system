<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\CirculationTransaction;
use App\Models\Employee;
use App\Models\ItemCopy;
use App\Models\Student;
use App\Models\User;
use App\Support\LibraryAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RecordCirculation
{
    /**
     * All loan limits, due dates, renewals and fines come from the approved regulation (P-017).
     * This action never computes a date or amount: any due date must be supplied with a decision reference.
     */
    public function __invoke(
        User $actor,
        ItemCopy $copy,
        string $transactionType,
        string $beneficiaryType,
        int $beneficiaryId,
        string $transactionNo,
        ?int $originalTransactionId = null,
        ?string $dueAt = null,
        ?string $dueDateReference = null,
        ?string $conditionNote = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): CirculationTransaction {
        if (! LibraryAccess::canManage($actor)) {
            throw new AuthorizationException('تداول النسخ مقصور على أمين المكتبة المخول.');
        }

        if (! in_array($transactionType, ['checkout', 'return', 'renewal', 'damage'], true)) {
            throw new InvalidArgumentException('نوع حركة التداول غير معروف.');
        }

        if (! in_array($beneficiaryType, [Student::class, Employee::class], true)) {
            throw new InvalidArgumentException('نوع المستفيد غير معروف.');
        }

        if (trim($transactionNo) === '') {
            throw new InvalidArgumentException('رقم الحركة مطلوب.');
        }

        $beneficiaryExists = $beneficiaryType === Student::class
            ? Student::query()->whereKey($beneficiaryId)->exists()
            : Employee::query()->whereKey($beneficiaryId)->exists();

        if (! $beneficiaryExists) {
            throw new InvalidArgumentException('المستفيد غير موجود.');
        }

        $original = null;

        if ($transactionType === 'checkout') {
            if ($originalTransactionId !== null) {
                throw new InvalidArgumentException('حركة الإعارة لا تشير إلى حركة سابقة.');
            }

            if ($copy->status !== 'available') {
                throw new InvalidArgumentException('النسخة غير متاحة للإعارة.');
            }

            if (trim((string) $dueAt) === '' || trim((string) $dueDateReference) === '') {
                throw new InvalidArgumentException('تاريخ الاستحقاق ومرجع قراره مطلوبان؛ لا يُفترض أجل إعارة.');
            }
        } else {
            if ($originalTransactionId === null) {
                throw new InvalidArgumentException('هذه الحركة تتطلب الإشارة إلى حركة الإعارة الأصلية.');
            }

            $original = CirculationTransaction::query()->findOrFail($originalTransactionId);

            if ((int) $original->item_copy_id !== (int) $copy->getKey()
                || $original->beneficiary_type !== $beneficiaryType
                || (int) $original->beneficiary_id !== (int) $beneficiaryId
                || $original->transaction_type !== 'checkout') {
                throw new InvalidArgumentException('الحركة الأصلية لا تطابق النسخة والمستفيد.');
            }

            if (in_array($transactionType, ['renewal', 'damage'], true)
                && (trim((string) $dueAt) === '' || trim((string) $dueDateReference) === '')) {
                throw new InvalidArgumentException('تاريخ الاستحقاق ومرجع قراره مطلوبان؛ لا يُفترض أجل تجديد.');
            }

            if ($transactionType === 'damage' && trim((string) $conditionNote) === '') {
                throw new InvalidArgumentException('وصف حالة النسخة مطلوب عند تسجيل التلف.');
            }
        }

        $due = $dueAt !== null ? Carbon::parse($dueAt) : null;
        $transaction = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $copy, $transactionType, $beneficiaryType, $beneficiaryId,
            $transactionNo, $original, $due, $dueDateReference, $conditionNote,
            $decisionReference, $correlationId, &$transaction,
        ): void {
            $transaction = CirculationTransaction::create([
                'transaction_no' => $transactionNo,
                'item_copy_id' => $copy->getKey(),
                'beneficiary_type' => $beneficiaryType,
                'beneficiary_id' => $beneficiaryId,
                'transaction_type' => $transactionType,
                'original_transaction_id' => $original?->getKey(),
                'due_at' => $due,
                'occurred_at' => now(),
                'condition_note' => $conditionNote,
                'acted_by' => $actor->getKey(),
                'status' => 'recorded',
                'decision_reference' => $dueDateReference ?: $decisionReference,
                'correlation_id' => $correlationId,
            ]);

            $copy->status = match ($transactionType) {
                'checkout', 'renewal' => 'on_loan',
                'return' => 'available',
                'damage' => 'damaged',
            };
            $copy->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'library.circulation_recorded',
                'subject_type' => CirculationTransaction::class,
                'subject_id' => (string) $transaction->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'transaction_no' => $transactionNo,
                    'transaction_type' => $transactionType,
                    'item_copy_id' => $copy->getKey(),
                    'copy_status' => $copy->status,
                ],
            ]);
        });

        return $transaction->refresh();
    }
}
