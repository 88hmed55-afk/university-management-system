<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Str;

final class ReportAudit
{
    /**
     * Filter values are not copied into the general audit log: only their keys,
     * row counts and checksums are recorded there.
     *
     * @param  array<string, mixed>  $after
     */
    public static function record(
        User $actor,
        string $action,
        string $subjectType,
        string $subjectId,
        array $after = [],
        ?string $reason = null,
        ?string $correlationId = null,
        string $outcome = 'success',
    ): void {
        AuditLog::create([
            'actor_id' => $actor->getKey(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'outcome' => $outcome,
            'correlation_id' => $correlationId ?? Str::uuid()->toString(),
            'reason' => $reason,
            'after' => $after,
        ]);
    }
}
