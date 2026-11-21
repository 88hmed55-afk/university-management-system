<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\BibliographicRecord;
use App\Models\LibraryBranch;
use App\Models\User;
use App\Support\LibraryAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AddBibliographicRecord
{
    /** Catalog metadata only; no classification scheme or language list is invented. */
    public function __invoke(
        User $actor,
        string $recordNo,
        string $title,
        LibraryBranch $branch,
        ?string $author = null,
        ?string $classificationCode = null,
        ?string $isbn = null,
        ?string $languageCode = null,
        ?string $publisher = null,
        ?int $publishedYear = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): BibliographicRecord {
        if (! LibraryAccess::canManage($actor)) {
            throw new AuthorizationException('إضافة سجلات الفهرس مقصورة على أمين المكتبة المخول.');
        }

        if (trim($recordNo) === '' || trim($title) === '') {
            throw new InvalidArgumentException('رقم السجل والعنوان مطلوبان.');
        }

        if ($branch->status !== 'active') {
            throw new InvalidArgumentException('لا تُضاف سجلات إلى فرع مؤرشف.');
        }

        $record = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $recordNo, $title, $branch, $author, $classificationCode,
            $isbn, $languageCode, $publisher, $publishedYear,
            $decisionReference, $correlationId, &$record,
        ): void {
            $record = BibliographicRecord::create([
                'record_no' => $recordNo,
                'title' => $title,
                'author' => $author,
                'classification_code' => $classificationCode,
                'isbn' => $isbn,
                'language_code' => $languageCode ?: 'ar',
                'publisher' => $publisher,
                'published_year' => $publishedYear,
                'library_branch_id' => $branch->getKey(),
                'status' => 'active',
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'library.bibliographic_record_added',
                'subject_type' => BibliographicRecord::class,
                'subject_id' => (string) $record->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['record_no' => $recordNo, 'library_branch_id' => $branch->getKey()],
            ]);
        });

        return $record->refresh();
    }
}
