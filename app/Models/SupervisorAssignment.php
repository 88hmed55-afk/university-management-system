<?php

namespace App\Models;

use Database\Factories\SupervisorAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SupervisorAssignment extends Model
{
    /** @use HasFactory<SupervisorAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'postgraduate_record_id', 'employee_id', 'role_code', 'valid_from',
        'valid_to', 'status', 'reason', 'nominated_by', 'approved_by', 'decision_reference',
    ];

    protected function casts(): array
    {
        return ['valid_from' => 'date', 'valid_to' => 'date'];
    }

    protected static function booted(): void
    {
        static::updating(function (SupervisorAssignment $assignment): void {
            if ($assignment->isDirty([
                'postgraduate_record_id', 'employee_id', 'role_code', 'valid_from',
            ])) {
                throw new LogicException('هوية تكليف الإشراف لا تُعدل؛ أنشئ تكليفاً جديداً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('تكليفات الإشراف لا تُحذف؛ تُغلق بتاريخ النهاية.');
        });
    }

    /** @return BelongsTo<PostgraduateRecord, $this> */
    public function record(): BelongsTo
    {
        return $this->belongsTo(PostgraduateRecord::class, 'postgraduate_record_id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
