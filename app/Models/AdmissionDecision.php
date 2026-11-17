<?php

namespace App\Models;

use Database\Factories\AdmissionDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AdmissionDecision extends Model
{
    /** @use HasFactory<AdmissionDecisionFactory> */
    use HasFactory;

    protected $fillable = [
        'application_id', 'decision', 'decided_by', 'decided_at',
        'reference', 'reason', 'correlation_id',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('قرارات القبول غير قابلة للتعديل؛ سجّل قراراً جديداً.');
        });

        static::deleting(function (): never {
            throw new LogicException('قرارات القبول غير قابلة للحذف.');
        });
    }

    public function decisionLabel(): string
    {
        return trans('approvals.'.$this->decision);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
