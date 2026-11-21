<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\RequestEvent;
use App\Models\ServiceRequest;
use App\Models\User;

final class RequestAudit
{
    /** Append-only request event plus the corresponding audit record. Call inside the caller's transaction. */
    public static function event(
        ServiceRequest $request,
        User $actor,
        string $eventType,
        ?string $note = null,
        ?string $correlationId = null,
    ): RequestEvent {
        $event = RequestEvent::create([
            'service_request_id' => $request->getKey(),
            'event_type' => $eventType,
            'actor_id' => $actor->getKey(),
            'note' => $note,
            'correlation_id' => $correlationId,
        ]);

        AuditLog::create([
            'actor_id' => $actor->getKey(),
            'action' => 'services.request_event',
            'subject_type' => RequestEvent::class,
            'subject_id' => (string) $event->getKey(),
            'outcome' => 'success',
            'correlation_id' => $correlationId,
            'reason' => $note,
            'after' => [
                'request_no' => $request->request_no,
                'event_type' => $eventType,
            ],
        ]);

        return $event;
    }
}
