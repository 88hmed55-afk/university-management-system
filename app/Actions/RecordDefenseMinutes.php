<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\DefenseSession;
use App\Models\Document;
use App\Models\User;
use App\RecordStatus;
use App\Support\PostgraduateAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RecordDefenseMinutes
{
    /** SCR-070: minutes are a document snapshot and are never edited or deleted. */
    public function __invoke(
        DefenseSession $session,
        User $actor,
        Document $minutes,
        string $recommendation,
        string $decisionReference,
        ?string $correlationId = null,
    ): DefenseSession {
        if (! PostgraduateAccess::canManage($actor)) {
            throw new AuthorizationException('حفظ محاضر المناقشة مقصور على عمادة الدراسات العليا.');
        }

        if (trim($recommendation) === '' || trim($decisionReference) === '') {
            throw new InvalidArgumentException('التوصية ومرجع القرار مطلوبان.');
        }

        if (! in_array($session->status, ['scheduled', 'confirmed'], true)) {
            throw new InvalidArgumentException('الجلسة ليست في حالة تسمح بحفظ المحضر.');
        }

        if (Document::query()->whereKey($minutes->getKey())->value('status') !== RecordStatus::Active) {
            throw new InvalidArgumentException('وثيقة المحضر يجب أن تكون فعّالة.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $session, $actor, $minutes, $recommendation, $decisionReference, $correlationId,
        ): void {
            $session->minutes_document_id = $minutes->getKey();
            $session->recommendation = $recommendation;
            $session->status = 'minutes_recorded';
            $session->decision_reference = $decisionReference;
            $session->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'postgraduate.defense_minutes_recorded',
                'subject_type' => DefenseSession::class,
                'subject_id' => (string) $session->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'session_no' => $session->session_no,
                    'minutes_document_id' => $minutes->getKey(),
                    'status' => 'minutes_recorded',
                ],
            ]);
        });

        return $session->refresh();
    }
}
