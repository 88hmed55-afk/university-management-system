<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\CourseSection;
use App\Models\GradeSubmission;
use App\Models\User;
use App\Support\SectionAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SubmitGrades
{
    public function __invoke(
        CourseSection $section,
        User $actor,
        ?string $notes = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): GradeSubmission {
        if (! SectionAccess::canRecordOn($actor, $section)) {
            throw new AuthorizationException('تقديم الدرجات مقصور على مدرس الشعبة أو التسجيل/القيادة الأكاديمية.');
        }

        $existing = GradeSubmission::query()
            ->where('course_section_id', $section->getKey())
            ->whereIn('status', ['draft', 'submitted'])
            ->first();

        if ($existing !== null) {
            throw new InvalidArgumentException('يوجد تقديم درجات مفتوح لهذه الشعبة بالفعل.');
        }

        $submission = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $section, $actor, $notes, $decisionReference, $correlationId, &$submission,
        ): void {
            $submission = GradeSubmission::create([
                'course_section_id' => $section->getKey(),
                'submitted_by' => $actor->getKey(),
                'submitted_at' => now(),
                'status' => 'submitted',
                'notes' => $notes,
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'grades.submitted',
                'subject_type' => GradeSubmission::class,
                'subject_id' => (string) $submission->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'course_section_id' => $section->getKey(),
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $submission->refresh();
    }
}
