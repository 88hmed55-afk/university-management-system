<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\BibliographicRecord;
use App\Models\ItemCopy;
use App\Models\LibraryBranch;
use App\Models\User;
use App\Support\LibraryAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AddItemCopy
{
    public function __invoke(
        User $actor,
        string $copyNo,
        BibliographicRecord $record,
        LibraryBranch $branch,
        ?string $locationCode = null,
        ?string $acquiredAt = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): ItemCopy {
        if (! LibraryAccess::canManage($actor)) {
            throw new AuthorizationException('إضافة نسخ مقصورة على أمين المكتبة المخول.');
        }

        if (trim($copyNo) === '') {
            throw new InvalidArgumentException('رقم النسخة مطلوب.');
        }

        if ($branch->status !== 'active' || $record->status !== 'active') {
            throw new InvalidArgumentException('الفرع أو السجل غير فعّال.');
        }

        if ((int) $record->library_branch_id !== (int) $branch->getKey()) {
            throw new InvalidArgumentException('نسخة السجل يجب أن تُضاف في فرع السجل نفسه.');
        }

        $copy = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $copyNo, $record, $branch, $locationCode,
            $acquiredAt, $decisionReference, $correlationId, &$copy,
        ): void {
            $copy = ItemCopy::create([
                'copy_no' => $copyNo,
                'bibliographic_record_id' => $record->getKey(),
                'library_branch_id' => $branch->getKey(),
                'location_code' => $locationCode,
                'status' => 'available',
                'acquired_at' => $acquiredAt,
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'library.item_copy_added',
                'subject_type' => ItemCopy::class,
                'subject_id' => (string) $copy->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'copy_no' => $copyNo,
                    'bibliographic_record_id' => $record->getKey(),
                    'library_branch_id' => $branch->getKey(),
                ],
            ]);
        });

        return $copy->refresh();
    }
}
