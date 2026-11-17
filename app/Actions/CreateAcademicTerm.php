<?php

namespace App\Actions;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CreateAcademicTerm
{
    /**
     * Create a dated term inside an academic year with an approval reference.
     */
    public function __invoke(
        AcademicYear $year,
        User $actor,
        string $code,
        string $name,
        Carbon $startDate,
        Carbon $endDate,
        string $decisionReference,
        ?Carbon $registrationOpen = null,
        ?Carbon $registrationClose = null,
        ?string $termTypeCode = null,
        ?string $correlationId = null,
    ): AcademicTerm {
        if (! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('تعديل التقويم الأكاديمي مقصور على القيادة الأكاديمية المخولين.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع اعتماد الفصل مطلوب.');
        }

        if ($startDate->lessThan($year->start_date) || $endDate->greaterThan($year->end_date)) {
            throw new InvalidArgumentException('تواريخ الفصل يجب أن تقع داخل السنة الأكاديمية.');
        }

        $term = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $year, $actor, $code, $name, $startDate, $endDate, $decisionReference,
            $registrationOpen, $registrationClose, $termTypeCode, $correlationId, &$term,
        ): void {
            $term = AcademicTerm::create([
                'academic_year_id' => $year->getKey(),
                'code' => $code,
                'name' => $name,
                'term_type_code' => $termTypeCode,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'registration_open_at' => $registrationOpen,
                'registration_close_at' => $registrationClose,
                'decision_reference' => $decisionReference,
                'approved_by' => $actor->getKey(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'academic.term_created',
                'subject_type' => AcademicTerm::class,
                'subject_id' => (string) $term->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'academic_year_id' => $year->getKey(),
                    'code' => $code,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $term->refresh();
    }
}
