<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\CashSession;
use App\Models\User;
use App\Support\FinanceAccess;
use App\Support\MoneyMath;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CloseCashSession
{
    /** Expected amount is derived from captured payments; differences require an explanation. */
    public function __invoke(
        CashSession $session,
        User $actor,
        string $actualAmount,
        ?string $differenceReason = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): CashSession {
        if (! FinanceAccess::canCapture($actor)) {
            throw new AuthorizationException('إقفال الوردية مقصور على أمين الصندوق والمالية.');
        }

        if ($session->status !== 'open') {
            throw new InvalidArgumentException('الوردية غير مفتوحة.');
        }

        $actualAmount = MoneyMath::nonNegative($actualAmount, 'المبلغ الفعلي');

        if ($actualAmount === null) {
            throw new InvalidArgumentException('المبلغ الفعلي مطلوب.');
        }

        $expected = '0.00';

        foreach ($session->payments()->where('status', 'captured')->pluck('amount') as $amount) {
            $expected = MoneyMath::add($expected, (string) $amount);
        }

        $difference = MoneyMath::subtract($actualAmount, $expected);

        if (bccomp($difference, '0', 2) !== 0 && trim((string) $differenceReason) === '') {
            throw new InvalidArgumentException('تفسير الفرق مطلوب عند وجود فروق.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $session, $actor, $actualAmount, $expected, $difference,
            $differenceReason, $decisionReference, $correlationId,
        ): void {
            $session->status = 'closed';
            $session->closed_by = $actor->getKey();
            $session->closed_at = now();
            $session->expected_amount = $expected;
            $session->actual_amount = $actualAmount;
            $session->difference_reason = $differenceReason;
            $session->decision_reference = $decisionReference;
            $session->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'cash.session_closed',
                'subject_type' => CashSession::class,
                'subject_id' => (string) $session->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'expected_amount' => $expected,
                    'actual_amount' => $actualAmount,
                    'difference' => $difference,
                    'difference_reason' => $differenceReason,
                ],
            ]);
        });

        return $session->refresh();
    }
}
