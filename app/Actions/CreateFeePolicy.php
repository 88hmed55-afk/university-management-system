<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\FeeItem;
use App\Models\FeePolicy;
use App\Models\FeePolicyItem;
use App\Models\User;
use App\RoleCode;
use App\Support\MoneyMath;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CreateFeePolicy
{
    /**
     * Create a draft, versioned fee policy. Amounts are supplied by the caller —
     * no currency, tax, discount or amount is assumed (P-012/P-013).
     *
     * @param  array<int, array{fee_item_id: int, amount: string, discount_amount?: ?string, due_date?: ?string, notes?: ?string}>  $items
     */
    public function __invoke(
        User $actor,
        string $code,
        int $versionNo,
        string $name,
        int $currencyId,
        string $effectiveFrom,
        ?string $effectiveTo,
        array $items,
        ?int $academicProgramId = null,
        ?string $notes = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): FeePolicy {
        if (! $actor->hasActiveRole(RoleCode::FinanceDirector)
            && ! $actor->hasActiveRole(RoleCode::FinancialLeadership)) {
            throw new AuthorizationException('إنشاء سياسات الرسوم مقصور على المالية والقيادة المالية.');
        }

        if (trim($code) === '' || trim($name) === '') {
            throw new InvalidArgumentException('رمز سياسة الرسوم واسمها مطلوبان.');
        }

        if ($versionNo < 1) {
            throw new InvalidArgumentException('رقم إصدار السياسة يجب أن يكون موجباً.');
        }

        $currency = Currency::findOrFail($currencyId);

        if ($currency->status !== 'active') {
            throw new InvalidArgumentException('العملة غير فعّالة.');
        }

        if ($effectiveTo !== null && strtotime($effectiveTo) < strtotime($effectiveFrom)) {
            throw new InvalidArgumentException('تاريخ نهاية السريان لا يمكن أن يسبق بدايته.');
        }

        if ($items === []) {
            throw new InvalidArgumentException('سياسة الرسوم تحتاج بنوداً واحدة على الأقل.');
        }

        $seen = [];
        $normalized = [];

        foreach ($items as $item) {
            $feeItemId = (int) ($item['fee_item_id'] ?? 0);

            if (isset($seen[$feeItemId])) {
                throw new InvalidArgumentException('تكرار بند الرسوم في السياسة.');
            }

            $seen[$feeItemId] = true;

            $feeItem = FeeItem::findOrFail($feeItemId);

            if ($feeItem->status !== 'active') {
                throw new InvalidArgumentException("بند الرسوم {$feeItem->code} غير فعّال.");
            }

            $amount = MoneyMath::nonNegative($item['amount'] ?? null, 'المبلغ');

            if ($amount === null) {
                throw new InvalidArgumentException('مبلغ بند الرسوم مطلوب.');
            }

            $discount = MoneyMath::nonNegative($item['discount_amount'] ?? null, 'الخصم');

            if ($discount !== null && bccomp($discount, $amount, 2) > 0) {
                throw new InvalidArgumentException('الخصم لا يمكن أن يتجاوز المبلغ (تحقق حسابي).');
            }

            $dueDate = $item['due_date'] ?? null;

            if ($dueDate !== null && strtotime($dueDate) === false) {
                throw new InvalidArgumentException('تاريخ الاستحقاق غير صالح.');
            }

            $normalized[] = [
                'fee_item_id' => $feeItemId,
                'amount' => $amount,
                'discount_amount' => $discount,
                'due_date' => $dueDate,
                'notes' => $item['notes'] ?? null,
            ];
        }

        $policy = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $code, $versionNo, $name, $academicProgramId, $currencyId,
            $effectiveFrom, $effectiveTo, $notes, $decisionReference,
            $normalized, $correlationId, &$policy,
        ): void {
            $policy = FeePolicy::create([
                'code' => $code,
                'version_no' => $versionNo,
                'name' => $name,
                'academic_program_id' => $academicProgramId,
                'currency_id' => $currencyId,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'status' => 'draft',
                'notes' => $notes,
                'decision_reference' => $decisionReference,
                'created_by' => $actor->getKey(),
            ]);

            foreach ($normalized as $item) {
                FeePolicyItem::create(array_merge(['fee_policy_id' => $policy->getKey()], $item));
            }

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.fee_policy_created',
                'subject_type' => FeePolicy::class,
                'subject_id' => (string) $policy->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'code' => $code,
                    'version_no' => $versionNo,
                    'currency_id' => $currencyId,
                    'effective_from' => $effectiveFrom,
                    'items' => count($normalized),
                ],
            ]);
        });

        return $policy->refresh();
    }
}
