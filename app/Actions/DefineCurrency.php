<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DefineCurrency
{
    public function __invoke(
        User $actor,
        string $code,
        string $name,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): Currency {
        if (! $actor->hasActiveRole(RoleCode::FinanceDirector)
            && ! $actor->hasActiveRole(RoleCode::FinancialLeadership)) {
            throw new AuthorizationException('تعريف العملات مقصور على المالية والقيادة المالية.');
        }

        if (trim($code) === '' || trim($name) === '') {
            throw new InvalidArgumentException('رمز العملة واسمها مطلوبان.');
        }

        $currency = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $code, $name, $decisionReference, $correlationId, &$currency,
        ): void {
            $currency = Currency::create([
                'code' => $code,
                'name' => $name,
                'status' => 'active',
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.currency_defined',
                'subject_type' => Currency::class,
                'subject_id' => (string) $currency->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['code' => $code, 'name' => $name],
            ]);
        });

        return $currency->refresh();
    }
}
