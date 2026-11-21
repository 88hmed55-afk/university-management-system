<?php

namespace App\Models;

use Database\Factories\NotificationTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class NotificationTemplate extends Model
{
    /** @use HasFactory<NotificationTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'version_no', 'channel_code', 'subject', 'body', 'status',
        'created_by', 'approved_by', 'approved_at', 'decision_reference',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (NotificationTemplate $template): void {
            if (in_array($template->getOriginal('status'), ['approved', 'published'], true)
                && $template->isDirty(['code', 'version_no', 'channel_code', 'subject', 'body'])) {
                throw new LogicException('قالب الإشعار المعتمد/المنشور لا يُعدل؛ أنشئ إصداراً جديداً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('قوالب الإشعارات لا تُحذف.');
        });
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
