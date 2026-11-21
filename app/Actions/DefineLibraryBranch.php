<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\LibraryBranch;
use App\Models\User;
use App\Support\LibraryAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class DefineLibraryBranch
{
    public function __invoke(
        User $actor,
        string $code,
        string $name,
        Campus $campus,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): LibraryBranch {
        if (! LibraryAccess::canManage($actor)) {
            throw new AuthorizationException('تعريف فروع المكتبة مقصور على أمين المكتبة المخول.');
        }

        if (trim($code) === '' || trim($name) === '') {
            throw new InvalidArgumentException('رمز الفرع واسمه مطلوبان.');
        }

        $branch = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $code, $name, $campus, $decisionReference, $correlationId, &$branch,
        ): void {
            $branch = LibraryBranch::create([
                'code' => $code,
                'name' => $name,
                'campus_id' => $campus->getKey(),
                'status' => 'active',
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'library.branch_defined',
                'subject_type' => LibraryBranch::class,
                'subject_id' => (string) $branch->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['code' => $code, 'campus_id' => $campus->getKey()],
            ]);
        });

        return $branch->refresh();
    }
}
