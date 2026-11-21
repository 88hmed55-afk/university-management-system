<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\BibliographicRecord;
use App\Models\Employee;
use App\Models\Reservation;
use App\Models\Student;
use App\Models\User;
use App\Support\LibraryAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ReserveBibliographicRecord
{
    /** A beneficiary may reserve only for themselves; no reservation count or expiry rule is invented. */
    public function __invoke(
        User $actor,
        BibliographicRecord $record,
        string $reservationNo,
        string $beneficiaryType,
        int $beneficiaryId,
        ?string $expiresAt = null,
        ?string $decisionReference = null,
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
    ): Reservation {
        if (! LibraryAccess::canViewBeneficiary($actor, $beneficiaryType, $beneficiaryId)) {
            throw new AuthorizationException('لا يمكن حجز سجل لمستفيد آخر (SCR-082).');
        }

        if (! in_array($beneficiaryType, [Student::class, Employee::class], true)) {
            throw new InvalidArgumentException('نوع المستفيد غير معروف.');
        }

        if (trim($reservationNo) === '') {
            throw new InvalidArgumentException('رقم الحجز مطلوب.');
        }

        if ($record->status !== 'active') {
            throw new InvalidArgumentException('السجل غير فعّال.');
        }

        if ($expiresAt !== null && trim((string) $decisionReference) === '') {
            throw new InvalidArgumentException('تاريخ انتهاء الحجز يتطلب مرجع قرار؛ لا يُفترض أجل حجز.');
        }

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $existing = Reservation::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing->refresh();
            }
        }

        $reservation = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $record, $reservationNo, $beneficiaryType, $beneficiaryId,
            $expiresAt, $decisionReference, $idempotencyKey, $correlationId, &$reservation,
        ): void {
            $reservation = Reservation::create([
                'reservation_no' => $reservationNo,
                'bibliographic_record_id' => $record->getKey(),
                'beneficiary_type' => $beneficiaryType,
                'beneficiary_id' => $beneficiaryId,
                'status' => 'requested',
                'expires_at' => $expiresAt !== null ? Carbon::parse($expiresAt) : null,
                'idempotency_key' => $idempotencyKey,
                'acted_by' => $actor->getKey(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'library.reservation_requested',
                'subject_type' => Reservation::class,
                'subject_id' => (string) $reservation->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'reservation_no' => $reservationNo,
                    'bibliographic_record_id' => $record->getKey(),
                    'beneficiary_type' => $beneficiaryType,
                ],
            ]);
        });

        return $reservation->refresh();
    }
}
