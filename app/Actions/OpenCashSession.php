<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Cashbox;
use App\Models\CashSession;
use App\Models\User;
use App\Support\FinanceAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class OpenCashSession
{
    public function __invoke(
        Cashbox $cashbox,
        User $actor,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): CashSession {
        if (! FinanceAccess::canCapture($actor)) {
            throw new AuthorizationException('فتح الوردية مقصور على أمين الصندوق والمالية.');
        }

        if ($cashbox->status !== 'active') {
            throw new InvalidArgumentException('الصندوق غير فعّال.');
        }

        if (CashSession::query()
            ->where('cashbox_id', $cashbox->getKey())
            ->where('status', 'open')
            ->exists()) {
            throw new InvalidArgumentException('يوجد وردية مفتوحة لهذا الصندوق بالفعل.');
        }

        $session = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($cashbox, $actor, $decisionReference, $correlationId, &$session): void {
            $session = CashSession::create([
                'cashbox_id' => $cashbox->getKey(),
                'opened_by' => $actor->getKey(),
                'opened_at' => now(),
                'status' => 'open',
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'cash.session_opened',
                'subject_type' => CashSession::class,
                'subject_id' => (string) $session->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['cashbox_id' => $cashbox->getKey()],
            ]);
        });

        return $session->refresh();
    }
}
