<?php

namespace App\Models;

use Database\Factories\WorkflowStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class WorkflowStep extends Model
{
    /** @use HasFactory<WorkflowStepFactory> */
    use HasFactory;

    protected $fillable = [
        'service_request_type_id', 'version_no', 'step_no', 'name',
        'role_code', 'scope_code', 'transition_condition', 'status',
        'decision_reference',
    ];

    protected static function booted(): void
    {
        static::updating(function (WorkflowStep $step): void {
            if ($step->getOriginal('status') === 'published'
                && $step->isDirty(['version_no', 'step_no', 'role_code', 'scope_code', 'transition_condition'])) {
                throw new LogicException('خطوة المسار المنشورة لا تُعدل؛ أنشئ إصداراً جديداً موثّقاً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('خطوات المسار لا تُحذف؛ تُعطّل بإصدار جديد.');
        });
    }

    /** @return BelongsTo<ServiceRequestType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ServiceRequestType::class, 'service_request_type_id');
    }
}
