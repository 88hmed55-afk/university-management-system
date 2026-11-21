<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\ResearchProposal;
use App\Models\User;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ApproveResearchProposal
{
    /** Approval is a documented decision; the approver must differ from the submitter. */
    public function __invoke(
        ResearchProposal $proposal,
        User $actor,
        string $decisionReference,
        ?string $conditionNote = null,
        ?string $correlationId = null,
    ): ResearchProposal {
        if (! PostgraduateAccess::canApprove($actor)) {
            throw new AuthorizationException('اعتماد مقترحات البحث مقصور على عمادة الدراسات العليا.');
        }

        if ((int) $proposal->submitted_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لفاعل واحد تقديم واعتماد المقترح.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع اعتماد المقترح مطلوب.');
        }

        if ($proposal->status !== 'submitted') {
            throw new InvalidArgumentException('المقترح ليس في حالة انتظار الاعتماد.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $proposal, $actor, $decisionReference, $conditionNote, $correlationId,
        ): void {
            $proposal->status = 'approved';
            $proposal->approved_by = $actor->getKey();
            $proposal->decision_reference = $decisionReference;
            $proposal->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.proposal_approved',
                'subject_type' => ResearchProposal::class,
                'subject_id' => (string) $proposal->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $conditionNote,
                'after' => [
                    'proposal_no' => $proposal->proposal_no,
                    'version_no' => $proposal->version_no,
                    'status' => 'approved',
                ],
            ]);
        });

        return $proposal->refresh();
    }
}
