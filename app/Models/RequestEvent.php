<?php

namespace App\Models;

use Database\Factories\RequestEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class RequestEvent extends Model
{
    /** @use HasFactory<RequestEventFactory> */
    use HasFactory;

    protected $fillable = [
        'service_request_id', 'event_type', 'actor_id', 'note', 'correlation_id',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('أحداث الطلب غير قابلة للتعديل.');
        });

        static::deleting(function (): never {
            throw new LogicException('أحداث الطلب غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<ServiceRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }
}
