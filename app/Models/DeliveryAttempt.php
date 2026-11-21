<?php

namespace App\Models;

use Database\Factories\DeliveryAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class DeliveryAttempt extends Model
{
    /** @use HasFactory<DeliveryAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'notification_id', 'attempt_no', 'channel_code', 'provider_code',
        'status', 'error', 'idempotency_key', 'correlation_id',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('محاولات الإرسال غير قابلة للتعديل.');
        });

        static::deleting(function (): never {
            throw new LogicException('محاولات الإرسال غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<Notification, $this> */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
