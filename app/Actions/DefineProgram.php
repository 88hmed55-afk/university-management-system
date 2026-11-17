<?php

namespace App\Actions;

use App\Models\AcademicProgram;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\User;
use App\RoleCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DefineProgram
{
    /**
     * Register a program once with exactly one responsible primary college (docs/02 line 35).
     */
    public function __invoke(
        User $actor,
        string $code,
        string $name,
        College $primaryCollege,
        ?string $levelCode = null,
        ?string $correlationId = null,
    ): AcademicProgram {
        if (! $actor->hasActiveRole(RoleCode::AcademicLeadership)) {
            throw new AuthorizationException('تعريف البرامج مقصور على القيادة الأكاديمية المخولين.');
        }

        $program = null;
        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $actor, $code, $name, $primaryCollege, $levelCode, $correlationId, &$program,
        ): void {
            $program = AcademicProgram::create([
                'code' => $code,
                'name' => $name,
                'level_code' => $levelCode,
                'primary_college_id' => $primaryCollege->getKey(),
            ]);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'academic.program_defined',
                'subject_type' => AcademicProgram::class,
                'subject_id' => (string) $program->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'after' => [
                    'code' => $code,
                    'primary_college_id' => $primaryCollege->getKey(),
                    'level_code' => $levelCode,
                ],
            ]);
        });

        return $program->refresh();
    }
}
