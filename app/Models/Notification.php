<?php

namespace App\Models;

use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'notification_no', 'recipient_id', 'notification_template_id',
        'channel_code', 'subject', 'body', 'audience_scope_code', 'action_url',
        'status', 'read_at', 'idempotency_key', 'correlation_id',
        'decision_reference',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (Notification $notification): void {
            $immutable = [
                'notification_no', 'recipient_id', 'notification_template_id',
                'channel_code', 'subject', 'body', 'audience_scope_code',
                'idempotency_key',
            ];

            if ($notification->isDirty($immutable)) {
                throw new LogicException('محتوى الإشعار ومستلمه لا يُعدلان.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('الإشعارات لا تُحذف.');
        });
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /** @return HasMany<DeliveryAttempt, $this> */
    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }
}
