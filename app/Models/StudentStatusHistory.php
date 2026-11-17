<?php

namespace App\Models;

use App\StudentStatus;
use Database\Factories\StudentStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class StudentStatusHistory extends Model
{
    /** @use HasFactory<StudentStatusHistoryFactory> */
    use HasFactory;

    protected $table = 'student_status_history';

    protected $fillable = [
        'student_id', 'from_status', 'to_status', 'reason', 'effective_date',
        'decision_reference', 'actor_id', 'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => StudentStatus::class,
            'to_status' => StudentStatus::class,
            'effective_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('تاريخ حالات الطلاب غير قابل للتعديل.');
        });

        static::deleting(function (): never {
            throw new LogicException('تاريخ حالات الطلاب غير قابل للحذف.');
        });
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
