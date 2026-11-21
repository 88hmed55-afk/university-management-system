<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\PostgraduateRecord;
use App\Models\ResearchMilestone;
use App\Models\User;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SubmitResearchMilestone
{
    /** SCR-069: dates and statuses come from the regulation; no schedule is inferred. */
    public function __invoke(
        User $actor,
        PostgraduateRecord $record,
        string $milestoneNo,
        string $title,
        ?string $reportNote = null,
        ?string $dueAt = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): ResearchMilestone {
        if (! PostgraduateAccess::canManage($actor) && ! $record->belongsToUser($actor)) {
            throw new AuthorizationException('لا يمكن تسجيل مرحلة لملف بحث آخر.');
        }

        if (trim($milestoneNo) === '' || trim($title) === '') {
            throw new InvalidArgumentException('رقم المرحلة وعنوانها مطلوبان.');
        }

        if ($dueAt !== null && trim((string) $decisionReference) === '') {
            throw new InvalidArgumentException('تاريخ المرحلة المستحق يتطلب مرجع قرار؛ لا يُفترض جدول زمني.');
        }

        $milestone = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $record, $milestoneNo, $title, $reportNote,
            $dueAt, $decisionReference, $correlationId, &$milestone,
        ): void {
            $milestone = ResearchMilestone::create([
                'milestone_no' => $milestoneNo,
                'postgraduate_record_id' => $record->getKey(),
                'title' => $title,
                'report_note' => $reportNote,
                'due_at' => $dueAt,
                'status' => 'submitted',
                'submitted_at' => now(),
                'submitted_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.milestone_submitted',
                'subject_type' => ResearchMilestone::class,
                'subject_id' => (string) $milestone->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['milestone_no' => $milestoneNo, 'status' => 'submitted'],
            ]);
        });

        return $milestone->refresh();
    }
}
