<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\BankReconciliation;
use App\Models\Cashbox;
use App\Models\User;
use App\Support\FinanceAccess;
use App\Support\MoneyMath;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RecordBankReconciliation
{
    public function __invoke(
        Cashbox $cashbox,
        User $actor,
        string $periodStart,
        string $periodEnd,
        string $expectedAmount,
        string $actualAmount,
        string $decisionReference,
        ?string $notes = null,
        ?string $correlationId = null,
    ): BankReconciliation {
        if (! FinanceAccess::canApprove($actor)) {
            throw new AuthorizationException('تسجيل المطابقات مقصور على المالية المخولة.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع المطابقة مطلوب.');
        }

        if (strtotime($periodEnd) < strtotime($periodStart)) {
            throw new InvalidArgumentException('نهاية الفترة لا يمكن أن تسبق بدايتها.');
        }

        $expected = MoneyMath::nonNegative($expectedAmount, 'المتوقع');
        $actual = MoneyMath::nonNegative($actualAmount, 'الفعلي');

        if ($expected === null || $actual === null) {
            throw new InvalidArgumentException('المبلغان المتوقع والفعلي مطلوبان.');
        }

        $reconciliation = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $cashbox, $actor, $periodStart, $periodEnd, $expected, $actual,
            $decisionReference, $notes, $correlationId, &$reconciliation,
        ): void {
            $reconciliation = BankReconciliation::create([
                'cashbox_id' => $cashbox->getKey(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'expected_amount' => $expected,
                'actual_amount' => $actual,
                'difference_amount' => MoneyMath::subtract($actual, $expected),
                'notes' => $notes,
                'status' => 'recorded',
                'prepared_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.bank_reconciliation_recorded',
                'subject_type' => BankReconciliation::class,
                'subject_id' => (string) $reconciliation->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'cashbox_id' => $cashbox->getKey(),
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'difference_amount' => $reconciliation->difference_amount,
                ],
            ]);
        });

        return $reconciliation->refresh();
    }
}
