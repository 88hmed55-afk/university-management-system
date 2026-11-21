<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\ServiceRequestType;
use App\Models\User;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DefineServiceRequestType
{
    public function __invoke(
        User $actor,
        string $code,
        string $name,
        ?string $description = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): ServiceRequestType {
        if (! ServicesAccess::canConfigure($actor)) {
            throw new AuthorizationException('تعريف أنواع الطلبات مقصور على العمليات المخولة.');
        }

        if (trim($code) === '' || trim($name) === '') {
            throw new InvalidArgumentException('رمز نوع الطلب واسمه مطلوبان.');
        }

        $type = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $code, $name, $description, $decisionReference, $correlationId, &$type,
        ): void {
            $type = ServiceRequestType::create([
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'status' => 'active',
                'created_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.request_type_defined',
                'subject_type' => ServiceRequestType::class,
                'subject_id' => (string) $type->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['code' => $code, 'name' => $name],
            ]);
        });

        return $type->refresh();
    }
}
