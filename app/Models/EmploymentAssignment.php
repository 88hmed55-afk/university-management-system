<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\EmploymentAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class EmploymentAssignment extends Model
{
    /** @use HasFactory<EmploymentAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'manager_employee_id',
        'supervisor_employee_id',
        'assignment_type_code',
        'college_id',
        'department_id',
        'campus_id',
        'valid_from',
        'valid_to',
        'decision_reference',
        'approved_by',
        'reason',
        'status',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EmploymentAssignment $assignment): void {
            $employeeId = (int) $assignment->employee_id;

            if ($assignment->manager_employee_id !== null && (int) $assignment->manager_employee_id === $employeeId) {
                throw new InvalidArgumentException('لا يجوز أن يكون الموظف مديراً مباشراً لنفسه.');
            }

            if ($assignment->supervisor_employee_id !== null && (int) $assignment->supervisor_employee_id === $employeeId) {
                throw new InvalidArgumentException('لا يجوز أن يكون الموظف مشرفاً تخصصياً لنفسه.');
            }

            if ($assignment->valid_to !== null && $assignment->valid_to->lessThanOrEqualTo($assignment->valid_from)) {
                throw new InvalidArgumentException('تاريخ نهاية التكليف يجب أن يكون بعد تاريخ بدايته.');
            }

            // One direct administrative manager per overlapping period. A parallel
            // specialized supervisor is allowed (docs/02 line 32).
            $overlapping = static::query()
                ->where('employee_id', $employeeId)
                ->where('status', RecordStatus::Active->value)
                ->when($assignment->exists, fn (Builder $query) => $query->whereKeyNot($assignment->getKey()))
                ->where(function (Builder $query) use ($assignment): void {
                    $query->whereNull('valid_to')
                        ->orWhere('valid_to', '>', $assignment->valid_from);
                })
                ->where(function (Builder $query) use ($assignment): void {
                    if ($assignment->valid_to !== null) {
                        $query->where('valid_from', '<', $assignment->valid_to);
                    }
                })
                ->get();

            $managerId = $assignment->manager_employee_id === null
                ? null
                : (int) $assignment->manager_employee_id;

            foreach ($overlapping as $other) {
                $otherManagerId = $other->manager_employee_id === null
                    ? null
                    : (int) $other->manager_employee_id;

                if ($otherManagerId !== $managerId) {
                    throw new InvalidArgumentException('تبعية إدارية مباشرة متضاربة في الفترة نفسها.');
                }
            }
        });

        static::deleting(function (): never {
            throw new LogicException('التكليفات لا تُحذف؛ تُغلق بتاريخ النهاية.');
        });
    }

    /**
     * @param  Builder<EmploymentAssignment>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RecordStatus::Active->value);
    }

    /**
     * @param  Builder<EmploymentAssignment>  $query
     */
    public function scopeCurrentlyValid(Builder $query): void
    {
        $query->active()
            ->where('valid_from', '<=', today())
            ->where(function (Builder $query): void {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>', today());
            });
    }

    /**
     * @param  Builder<EmploymentAssignment>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $campusIds = $user->visibleCampusIds();

        if ($campusIds === null) {
            return;
        }

        $query->whereIn('campus_id', $campusIds);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_employee_id');
    }

    /**
     * @return BelongsTo<College, $this>
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Campus, $this>
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
