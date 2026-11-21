<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ChangeSupervisorAssignment
{
    /** SCR-067: a change is only by decision; the previous assignment is closed, never deleted or edited. */
    public function __invoke(
        SupervisorAssignment $assignment,
        User $actor,
        Employee $newSupervisor,
        string $roleCode,
        string $validFrom,
        string $reason,
        string $decisionReference,
        ?string $eligibilityReference = null,
        ?string $correlationId = null,
    ): SupervisorAssignment {
        if (! PostgraduateAccess::canManage($actor)) {
            throw new AuthorizationException('تغيير الإشراف مقصور على عمادة الدراسات العليا.');
        }

        if (trim($reason) === '' || trim($decisionReference) === '' || trim((string) $eligibilityReference) === '') {
            throw new InvalidArgumentException('سبب التغيير ومرجع القرار ومرجع الأهلية مطلوبان.');
        }

        if ($assignment->status !== 'approved') {
            throw new InvalidArgumentException('يمكن تغيير التكليفات المعتمدة فقط.');
        }

        if (trim($validFrom) === '' || trim($roleCode) === '') {
            throw new InvalidArgumentException('دور الإشراف وتاريخ البداية مطلوبان.');
        }

        $created = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $assignment, $actor, $newSupervisor, $roleCode, $validFrom,
            $reason, $decisionReference, $eligibilityReference,
            $correlationId, &$created,
        ): void {
            $previousEnd = date('Y-m-d', strtotime($validFrom.' -1 day'));

            $assignment->valid_to = $previousEnd;
            $assignment->status = 'closed';
            $assignment->decision_reference = $decisionReference;
            $assignment->save();

            $created = SupervisorAssignment::create([
                'postgraduate_record_id' => $assignment->postgraduate_record_id,
                'employee_id' => $newSupervisor->getKey(),
                'role_code' => $roleCode,
                'valid_from' => $validFrom,
                'status' => 'nominated',
                'reason' => $reason,
                'nominated_by' => $actor->getKey(),
                'decision_reference' => $decisionReference.'|eligibility:'.$eligibilityReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.supervisor_changed',
                'subject_type' => SupervisorAssignment::class,
                'subject_id' => (string) $created->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'before' => ['assignment_id' => $assignment->getKey(), 'status' => 'approved'],
                'after' => [
                    'assignment_id' => $created->getKey(),
                    'status' => 'nominated',
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $created->refresh();
    }
}
