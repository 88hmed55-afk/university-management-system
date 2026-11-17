<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\FeeItem;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DefineFeeItem
{
    public function __invoke(
        User $actor,
        string $code,
        string $name,
        ?string $description = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): FeeItem {
        if (! $actor->hasActiveRole(RoleCode::FinanceDirector)
            && ! $actor->hasActiveRole(RoleCode::FinancialLeadership)) {
            throw new AuthorizationException('تعريف بنود الرسوم مقصور على المالية والقيادة المالية.');
        }

        if (trim($code) === '' || trim($name) === '') {
            throw new InvalidArgumentException('رمز بند الرسوم واسمه مطلوبان.');
        }

        $item = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $code, $name, $description, $decisionReference, $correlationId, &$item,
        ): void {
            $item = FeeItem::create([
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'status' => 'active',
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'finance.fee_item_defined',
                'subject_type' => FeeItem::class,
                'subject_id' => (string) $item->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['code' => $code, 'name' => $name],
            ]);
        });

        return $item->refresh();
    }
}
