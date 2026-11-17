<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\PersonIdentifier;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RevealPersonIdentifier
{
    /**
     * Reveal an official identifier to an explicitly authorized actor and audit
     * the access. The identifier value itself is never written to the audit log.
     */
    public function __invoke(
        PersonIdentifier $identifier,
        User $actor,
        string $reason,
        ?string $correlationId = null,
    ): string {
        if (Gate::forUser($actor)->denies('view', $identifier)) {
            throw new AuthorizationException('الوصول إلى المعرفات الرسمية مقيد.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('سبب الاطلاع على المعرف الرسمي مطلوب.');
        }

        AuditLog::create([
            'actor_id' => $actor->getKey(),
            'action' => 'person_identifier.revealed',
            'subject_type' => PersonIdentifier::class,
            'subject_id' => (string) $identifier->getKey(),
            'outcome' => 'success',
            'correlation_id' => $correlationId ?? Str::uuid()->toString(),
            'reason' => $reason,
            'after' => [
                'person_id' => $identifier->person_id,
                'identifier_type_id' => $identifier->identifier_type_id,
            ],
        ]);

        return (string) $identifier->value;
    }
}
