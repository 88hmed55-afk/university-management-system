<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\LibraryBranch;
use App\Models\User;
use App\Support\LibraryAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ArchiveLibraryBranch
{
    /** Archiving preserves all historical records; a branch is never deleted. */
    public function __invoke(
        LibraryBranch $branch,
        User $actor,
        string $reason,
        string $decisionReference,
        ?string $correlationId = null,
    ): LibraryBranch {
        if (! LibraryAccess::canManage($actor)) {
            throw new AuthorizationException('أرشفة فروع المكتبة مقصورة على أمين المكتبة المخول.');
        }

        if (trim($reason) === '' || trim($decisionReference) === '') {
            throw new InvalidArgumentException('سبب الأرشفة ومرجع القرار مطلوبان.');
        }

        if ($branch->status === 'archived') {
            throw new InvalidArgumentException('الفرع مؤرشف بالفعل.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $branch, $actor, $reason, $decisionReference, $correlationId,
        ): void {
            $branch->status = 'archived';
            $branch->archived_at = now();
            $branch->decision_reference = $decisionReference;
            $branch->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'library.branch_archived',
                'subject_type' => LibraryBranch::class,
                'subject_id' => (string) $branch->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'after' => ['status' => 'archived', 'decision_reference' => $decisionReference],
            ]);
        });

        return $branch->refresh();
    }
}
