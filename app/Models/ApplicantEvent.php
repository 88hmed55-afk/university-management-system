<?php

namespace App\Models;

use Database\Factories\ApplicantEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ApplicantEvent extends Model
{
    /** @use HasFactory<ApplicantEventFactory> */
    use HasFactory;

    protected $fillable = ['application_id', 'event_type', 'actor_id', 'note', 'correlation_id'];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('أحداث المتقدم غير قابلة للتعديل.');
        });

        static::deleting(function (): never {
            throw new LogicException('أحداث المتقدم غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
