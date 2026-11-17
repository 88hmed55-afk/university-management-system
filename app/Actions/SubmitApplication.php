<?php

namespace App\Actions;

use App\Models\AdmissionCycle;
use App\Models\ApplicantEvent;
use App\Models\Application;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SubmitApplication
{
    /**
     * Idempotent: the same key always returns the same application.
     */
    public function __invoke(
        AdmissionCycle $cycle,
        Person $person,
        string $idempotencyKey,
        ?string $programCode = null,
        ?string $correlationId = null,
    ): Application {
        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('مفتاح تكرار الطلب مطلوب.');
        }

        $existing = Application::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing !== null) {
            return $existing;
        }

        $application = null;

        DB::transaction(function () use (
            $cycle, $person, $idempotencyKey, $programCode, $correlationId, &$application,
        ): void {
            $application = Application::create([
                'admission_cycle_id' => $cycle->getKey(),
                'person_id' => $person->getKey(),
                'application_no' => (string) Str::uuid(),
                'program_code' => $programCode,
                'idempotency_key' => $idempotencyKey,
                'submitted_at' => now(),
            ]);

            ApplicantEvent::create([
                'application_id' => $application->getKey(),
                'event_type' => 'submitted',
                'correlation_id' => $correlationId ?? Str::uuid()->toString(),
            ]);
        });

        return $application->refresh();
    }
}
