<?php

namespace App\Models;

use Database\Factories\ServiceRequestTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ServiceRequestType extends Model
{
    /** @use HasFactory<ServiceRequestTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'description', 'status', 'current_workflow_version_no',
        'created_by', 'decision_reference',
    ];

    protected static function booted(): void
    {
        static::updating(function (ServiceRequestType $type): void {
            if ($type->isDirty('code')) {
                throw new LogicException('رمز نوع الطلب لا يُعدل؛ أنشئ نوعاً جديداً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('أنواع الطلبات لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @return HasMany<WorkflowStep, $this> */
    public function workflowSteps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class);
    }

    /** @return HasMany<ServiceRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }
}
