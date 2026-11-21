<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\PostgraduateRecord;
use App\Models\ResearchProposal;
use App\Models\User;
use App\RecordStatus;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SubmitResearchProposal
{
    /** SCR-068: every version is retained; access is restricted to the researcher and authorized staff. */
    public function __invoke(
        User $actor,
        PostgraduateRecord $record,
        string $proposalNo,
        int $versionNo,
        string $title,
        string $abstract,
        ?Document $document = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): ResearchProposal {
        if (! PostgraduateAccess::canManage($actor) && ! $record->belongsToUser($actor)) {
            throw new AuthorizationException('لا يمكن رفع مقترح لملف بحث آخر.');
        }

        if (trim($proposalNo) === '' || trim($title) === '' || trim($abstract) === '') {
            throw new InvalidArgumentException('رقم المقترح وعنوانه وملخصه مطلوبة.');
        }

        if ($versionNo < 1) {
            throw new InvalidArgumentException('رقم إصدار المقترح يجب أن يكون موجباً.');
        }

        if (ResearchProposal::query()
            ->where('postgraduate_record_id', $record->getKey())
            ->where('version_no', $versionNo)
            ->exists()) {
            throw new InvalidArgumentException('إصدار المقترح معرّف مسبقاً.');
        }

        if ($document !== null
            && Document::query()->whereKey($document->getKey())->value('status') !== RecordStatus::Active) {
            throw new InvalidArgumentException('وثيقة المقترح يجب أن تكون فعّالة.');
        }

        $proposal = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $record, $proposalNo, $versionNo, $title, $abstract,
            $document, $decisionReference, $correlationId, &$proposal,
        ): void {
            $proposal = ResearchProposal::create([
                'proposal_no' => $proposalNo,
                'postgraduate_record_id' => $record->getKey(),
                'version_no' => $versionNo,
                'title' => $title,
                'abstract' => $abstract,
                'document_id' => $document?->getKey(),
                'status' => 'submitted',
                'submitted_at' => now(),
                'submitted_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.proposal_submitted',
                'subject_type' => ResearchProposal::class,
                'subject_id' => (string) $proposal->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'proposal_no' => $proposalNo,
                    'version_no' => $versionNo,
                    'postgraduate_record_id' => $record->getKey(),
                ],
            ]);
        });

        return $proposal->refresh();
    }
}
