<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\PostgraduateRecord;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class NominateSupervisor
{
    /**
     * The number and eligibility of supervisors are set by the regulation (P-016).
     * No count limit and no eligibility inference are applied here; both references are mandatory.
     */
    public function __invoke(
        User $actor,
        PostgraduateRecord $record,
        Employee $employee,
        string $roleCode,
        string $validFrom,
        ?string $validTo = null,
        ?string $reason = null,
        ?string $decisionReference = null,
        ?string $eligibilityReference = null,
        ?string $correlationId = null,
    ): SupervisorAssignment {
        if (! PostgraduateAccess::canManage($actor)) {
            throw new AuthorizationException('ترشيح المشرفين مقصور على عمادة الدراسات العليا.');
        }

        if (trim($roleCode) === '' || trim($validFrom) === '') {
            throw new InvalidArgumentException('دور الإشراف وتاريخ البداية مطلوبان.');
        }

        if (trim((string) $decisionReference) === '' || trim((string) $eligibilityReference) === '') {
            throw new InvalidArgumentException('مرجع القرار ومرجع الأهلية مطلوبان؛ لا يُفترض عدد أو أهلية إشراف.');
        }

        if ($record->status !== 'active') {
            throw new InvalidArgumentException('ملف الدراسات العليا غير فعّال.');
        }

        $assignment = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $record, $employee, $roleCode, $validFrom, $validTo,
            $reason, $decisionReference, $eligibilityReference,
            $correlationId, &$assignment,
        ): void {
            $assignment = SupervisorAssignment::create([
                'postgraduate_record_id' => $record->getKey(),
                'employee_id' => $employee->getKey(),
                'role_code' => $roleCode,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'status' => 'nominated',
                'reason' => $reason,
                'nominated_by' => $actor->getKey(),
                'decision_reference' => $decisionReference.'|eligibility:'.$eligibilityReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.supervisor_nominated',
                'subject_type' => SupervisorAssignment::class,
                'subject_id' => (string) $assignment->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'postgraduate_record_id' => $record->getKey(),
                    'employee_id' => $employee->getKey(),
                    'role_code' => $roleCode,
                    'eligibility_reference' => $eligibilityReference,
                ],
            ]);
        });

        return $assignment->refresh();
    }
}
