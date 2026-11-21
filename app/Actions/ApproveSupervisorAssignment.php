<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ApproveSupervisorAssignment
{
    /** The approver must differ from the nominator; no count rule is evaluated. */
    public function __invoke(
        SupervisorAssignment $assignment,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): SupervisorAssignment {
        if (! PostgraduateAccess::canApprove($actor)) {
            throw new AuthorizationException('اعتماد تكليف الإشراف مقصور على عمادة الدراسات العليا.');
        }

        if ((int) $assignment->nominated_by === (int) $actor->getKey()) {
            throw new AuthorizationException('لا يمكن لفاعل واحد ترشيح واعتماد المشرف.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع اعتماد التكليف مطلوب.');
        }

        if ($assignment->status !== 'nominated') {
            throw new InvalidArgumentException('التكليف ليس في حالة انتظار الاعتماد.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use ($assignment, $actor, $decisionReference, $correlationId): void {
            $assignment->status = 'approved';
            $assignment->approved_by = $actor->getKey();
            $assignment->decision_reference = $decisionReference;
            $assignment->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.supervisor_approved',
                'subject_type' => SupervisorAssignment::class,
                'subject_id' => (string) $assignment->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => ['status' => 'approved', 'decision_reference' => $decisionReference],
            ]);
        });

        return $assignment->refresh();
    }
}
