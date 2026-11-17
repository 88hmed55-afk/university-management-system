<?php

namespace App\Actions;

use App\Models\AssessmentComponent;
use App\Models\AuditLog;
use App\Models\CourseSection;
use App\Models\User;
use App\Support\SectionAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DefineAssessmentComponent
{
    public function __invoke(
        CourseSection $section,
        User $actor,
        string $code,
        string $name,
        ?string $typeCode = null,
        ?string $maxScore = null,
        ?string $decisionReference = null,
        ?string $correlationId = null,
    ): AssessmentComponent {
        if (! SectionAccess::canRecordOn($actor, $section)) {
            throw new AuthorizationException('تعريف عناصر التقييم مقصور على مدرس الشعبة أو التسجيل/القيادة الأكاديمية.');
        }

        $component = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $section, $actor, $code, $name, $typeCode, $maxScore,
            $decisionReference, $correlationId, &$component,
        ): void {
            $component = AssessmentComponent::create([
                'course_section_id' => $section->getKey(),
                'code' => $code,
                'name' => $name,
                'type_code' => $typeCode,
                'max_score' => $maxScore,
                'decision_reference' => $decisionReference,
                'approved_by' => $actor->getKey(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'assessment.component_defined',
                'subject_type' => AssessmentComponent::class,
                'subject_id' => (string) $component->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'course_section_id' => $section->getKey(),
                    'code' => $code,
                    'max_score' => $maxScore,
                ],
            ]);
        });

        return $component->refresh();
    }
}
