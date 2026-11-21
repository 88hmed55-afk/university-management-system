<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\ResearchMilestone;
use App\Models\User;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class EvaluateResearchMilestone
{
    /** The evaluator must differ from the submitter; no pass/fail rule is inferred. */
    public function __invoke(
        ResearchMilestone $milestone,
        User $actor,
        string $evaluation,
        string $decisionReference,
        ?string $correlationId = null,
    ): ResearchMilestone {
        if (! PostgraduateAccess::canEvaluate($actor)) {
            throw new AuthorizationException('تقييم مراحل البحث مقصور على المشرف أو عمادة الدراسات العليا.');
        }

        if ((int) $milestone->submitted_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لفاعل واحد تقديم وتقييم المرحلة.');
        }

        if (trim($evaluation) === '' || trim($decisionReference) === '') {
            throw new InvalidArgumentException('تقييم المشرف ومرجع القرار مطلوبان.');
        }

        if ($milestone->status !== 'submitted') {
            throw new InvalidArgumentException('المرحلة ليست في حالة انتظار التقييم.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $milestone, $actor, $evaluation, $decisionReference, $correlationId,
        ): void {
            $milestone->supervisor_evaluation = $evaluation;
            $milestone->evaluated_by = $actor->getKey();
            $milestone->status = 'evaluated';
            $milestone->decision_reference = $decisionReference;
            $milestone->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.milestone_evaluated',
                'subject_type' => ResearchMilestone::class,
                'subject_id' => (string) $milestone->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['milestone_no' => $milestone->milestone_no, 'status' => 'evaluated'],
            ]);
        });

        return $milestone->refresh();
    }
}
