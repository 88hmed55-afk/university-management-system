<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\DefenseSession;
use App\Models\User;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CompleteDefenseSession
{
    /**
     * Recording a completed defense is NOT a graduation decision.
     * Compliance with discussion requirements is evidenced by the supplied reference, not inferred here.
     */
    public function __invoke(
        DefenseSession $session,
        User $actor,
        string $decisionReference,
        ?string $requirementsEvidenceReference = null,
        ?string $correlationId = null,
    ): DefenseSession {
        if (! PostgraduateAccess::canApprove($actor)) {
            throw new AuthorizationException('اعتماد نتائج المناقشة مقصور على عمادة الدراسات العليا.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار إتمام المناقشة مطلوب.');
        }

        if ($session->status !== 'minutes_recorded') {
            throw new InvalidArgumentException('الجلسة ليست جاهزة للإتمام؛ يجب حفظ المحضر أولاً.');
        }

        if ($session->minutes_document_id === null || trim((string) $session->recommendation) === '') {
            throw new InvalidArgumentException('لا يمكن إتمام جلسة بلا محضر وتوصية.');
        }

        if (trim((string) $requirementsEvidenceReference) === '') {
            throw new InvalidArgumentException('مرجع إثبات متطلبات المناقشة مطلوب؛ لا يُفترض استيفاؤها.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $session, $actor, $decisionReference, $requirementsEvidenceReference, $correlationId,
        ): void {
            $session->status = 'completed';
            $session->decision_reference = $decisionReference;
            $session->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.defense_completed',
                'subject_type' => DefenseSession::class,
                'subject_id' => (string) $session->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'session_no' => $session->session_no,
                    'status' => 'completed',
                    'requirements_evidence_reference' => $requirementsEvidenceReference,
                    'is_graduation_decision' => false,
                ],
            ]);
        });

        return $session->refresh();
    }
}
