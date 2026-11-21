<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Reservation;
use App\Models\User;
use App\Support\LibraryAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CancelReservation
{
    /** Cancellation is documented and never a delete; no automatic cancellation rule is invented. */
    public function __invoke(
        Reservation $reservation,
        User $actor,
        string $reason,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): Reservation {
        if (! LibraryAccess::canViewBeneficiary(
            $actor, $reservation->beneficiary_type, (int) $reservation->beneficiary_id,
        )) {
            throw new AuthorizationException('لا يمكن إلغاء حجز مستفيد آخر (SCR-082).');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('سبب إلغاء الحجز مطلوب.');
        }

        if (! in_array($reservation->status, ['requested', 'approved'], true)) {
            throw new InvalidArgumentException('الحجز ليس في حالة تسمح بالإلغاء.');
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $reservation, $actor, $reason, $decisionReference, $correlationId,
        ): void {
            $reservation->status = 'cancelled';
            $reservation->decision_reference = $decisionReference;
            $reservation->save();

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'library.reservation_cancelled',
                'subject_type' => Reservation::class,
                'subject_id' => (string) $reservation->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'after' => ['reservation_no' => $reservation->reservation_no, 'status' => 'cancelled'],
            ]);
        });

        return $reservation->refresh();
    }
}
