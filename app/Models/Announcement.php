<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    protected $fillable = [
        'announcement_no', 'title', 'body', 'locale', 'audience_scope_code',
        'scope_key', 'status', 'publish_at', 'expires_at', 'published_at',
        'created_by', 'published_by', 'decision_reference',
    ];

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Announcement $announcement): void {
            $locked = in_array($announcement->getOriginal('status'), ['published', 'scheduled', 'stopped'], true);

            if ($locked && $announcement->isDirty([
                'announcement_no', 'title', 'body', 'locale',
                'audience_scope_code', 'scope_key',
            ])) {
                throw new LogicException('الإعلان المنشور لا يُعدل؛ أنشئ إعلاناً جديداً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('الإعلانات لا تُحذف؛ تُوقف بإجراء موثّق.');
        });
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
