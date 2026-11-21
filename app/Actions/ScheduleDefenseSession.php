<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\DefenseSession;
use App\Models\PostgraduateRecord;
use App\Models\ResearchProposal;
use App\Models\User;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ScheduleDefenseSession
{
    /**
     * SCR-070: scheduling records the reference to the approved defense requirements;
     * this action never evaluates graduation requirements itself (P-016).
     */
    public function __invoke(
        User $actor,
        PostgraduateRecord $record,
        string $sessionNo,
        string $scheduledAt,
        string $requirementsReference,
        ?string $committeeReference = null,
        ?string $location = null,
        ?ResearchProposal $proposal = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): DefenseSession {
        if (! PostgraduateAccess::canManage($actor)) {
            throw new AuthorizationException('جدولة المناقشات مقصورة على عمادة الدراسات العليا.');
        }

        if (trim($sessionNo) === '' || trim($requirementsReference) === '') {
            throw new InvalidArgumentException('رقم الجلسة ومرجع متطلبات المناقشة مطلوبان.');
        }

        if ($proposal !== null) {
            if ((int) $proposal->postgraduate_record_id !== (int) $record->getKey()) {
                throw new InvalidArgumentException('المقترح لا يتبع ملف البحث هذا.');
            }

            if (! in_array($proposal->status, ['submitted', 'approved'], true)) {
                throw new InvalidArgumentException('لا تُجدل مناقشة على مقترح غير معتمد أو مقدم.');
            }
        }

        $session = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $record, $sessionNo, $scheduledAt, $requirementsReference,
            $committeeReference, $location, $proposal, $decisionReference,
            $correlationId, &$session,
        ): void {
            $session = DefenseSession::create([
                'session_no' => $sessionNo,
                'postgraduate_record_id' => $record->getKey(),
                'research_proposal_id' => $proposal?->getKey(),
                'scheduled_at' => Carbon::parse($scheduledAt),
                'location' => $location,
                'committee_reference' => $committeeReference,
                'requirements_reference' => $requirementsReference,
                'status' => 'scheduled',
                'acted_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.defense_scheduled',
                'subject_type' => DefenseSession::class,
                'subject_id' => (string) $session->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'session_no' => $sessionNo,
                    'requirements_reference' => $requirementsReference,
                    'scheduled_at' => $session->scheduled_at->toISOString(),
                ],
            ]);
        });

        return $session->refresh();
    }
}
