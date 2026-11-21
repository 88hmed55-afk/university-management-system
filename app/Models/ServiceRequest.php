<?php

namespace App\Models;

use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'request_no', 'service_request_type_id', 'requester_type', 'requester_id',
        'description', 'status', 'assigned_to', 'workflow_version_no',
        'current_step_no', 'channel_code', 'idempotency_key',
        'decision_reference', 'submitted_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (ServiceRequest $request): void {
            $immutable = [
                'request_no', 'service_request_type_id', 'requester_type',
                'requester_id', 'description', 'submitted_at', 'idempotency_key',
            ];

            if ($request->isDirty($immutable)) {
                throw new LogicException('هوية الطلب الأصلية لا تُعدل؛ سجّل حدثاً موثّقاً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('طلبات الخدمات لا تُحذف.');
        });
    }

    /** @return BelongsTo<ServiceRequestType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ServiceRequestType::class, 'service_request_type_id');
    }

    public function requester(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<RequestEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(RequestEvent::class);
    }
}
