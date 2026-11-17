<?php

namespace App\Actions;

use App\Models\AssessmentComponent;
use App\Models\AssessmentScore;
use App\Models\AuditLog;
use App\Models\CourseEnrollment;
use App\Models\GradeSubmission;
use App\Models\User;
use App\Support\SectionAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RecordScore
{
    public function __invoke(
        CourseEnrollment $enrollment,
        AssessmentComponent $component,
        User $actor,
        ?string $rawScore,
        ?string $notes = null,
        ?string $correlationId = null,
    ): AssessmentScore {
        $section = $enrollment->section;

        if (! SectionAccess::canRecordOn($actor, $section)) {
            throw new AuthorizationException('إدخال الدرجات مقصور على مدرس الشعبة أو التسجيل/القيادة الأكاديمية.');
        }

        if ((int) $component->course_section_id !== (int) $section->getKey()) {
            throw new InvalidArgumentException('عنصر التقييم لا ينتمي إلى شعبة الطالب.');
        }

        if (GradeSubmission::query()
            ->where('course_section_id', $section->getKey())
            ->where('status', 'approved')
            ->exists()) {
            throw new InvalidArgumentException('لا يمكن تعديل الدرجات بعد اعتماد التقديم.');
        }

        if ($rawScore !== null && ! is_numeric($rawScore)) {
            throw new InvalidArgumentException('الدرجة الخام يجب أن تكون قيمة عددية.');
        }

        $score = null;
        $before = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $enrollment, $component, $actor, $rawScore, $notes,
            $correlationId, &$score, &$before,
        ): void {
            $score = AssessmentScore::query()
                ->where('course_enrollment_id', $enrollment->getKey())
                ->where('assessment_component_id', $component->getKey())
                ->first();

            if ($score === null) {
                $score = AssessmentScore::create([
                    'course_enrollment_id' => $enrollment->getKey(),
                    'assessment_component_id' => $component->getKey(),
                    'raw_score' => $rawScore,
                    'notes' => $notes,
                    'entered_by' => $actor->getKey(),
                    'entered_at' => now(),
                ]);
            } else {
                $before = ['raw_score' => $score->raw_score, 'notes' => $score->notes];
                $score->raw_score = $rawScore;
                $score->notes = $notes;
                $score->entered_by = $actor->getKey();
                $score->entered_at = now();
                $score->save();
            }

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'assessment.score_saved',
                'subject_type' => AssessmentScore::class,
                'subject_id' => (string) $score->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'before' => $before,
                'after' => [
                    'course_enrollment_id' => $enrollment->getKey(),
                    'assessment_component_id' => $component->getKey(),
                    'raw_score' => $rawScore,
                ],
            ]);
        });

        return $score->refresh();
    }
}
