<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\PublishedResult;
use App\Models\Student;
use App\Models\StudentTranscript;
use App\Models\StudyPlanVersion;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class GenerateStudentTranscript
{
    public function __invoke(
        Student $student,
        ?StudyPlanVersion $plan,
        User $actor,
        string $decisionReference,
        ?string $correlationId = null,
    ): StudentTranscript {
        if (! $actor->hasActiveRole(RoleCode::RegistrationOfficer)
            && ! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('إصدار كشف الدرجات مقصور على التسجيل والقيادة الأكاديمية.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع إصدار الكشف مطلوب.');
        }

        $published = PublishedResult::query()
            ->whereHas('enrollment', fn ($query) => $query->where('student_id', $student->getKey()))
            ->whereNull('withdrawn_at')
            ->with('enrollment.section.offering.course')
            ->get();

        $payload = [
            'student_id' => $student->getKey(),
            'study_plan_version_id' => $plan?->getKey(),
            'results' => $published->map(fn ($result) => [
                'course_id' => $result->enrollment->section->offering->course_id,
                'payload' => $result->payload,
            ])->all(),
        ];

        $transcript = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $student, $plan, $actor, $decisionReference, $payload, $correlationId, &$transcript,
        ): void {
            $transcript = StudentTranscript::create([
                'student_id' => $student->getKey(),
                'study_plan_version_id' => $plan?->getKey(),
                'payload' => $payload,
                'generated_by' => $actor->getKey(),
                'generated_at' => now(),
                'decision_reference' => $decisionReference,
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'student.transcript_generated',
                'subject_type' => StudentTranscript::class,
                'subject_id' => (string) $transcript->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'student_id' => $student->getKey(),
                    'study_plan_version_id' => $plan?->getKey(),
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $transcript->refresh();
    }
}
