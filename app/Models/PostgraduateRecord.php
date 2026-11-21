<?php

namespace App\Models;

use Database\Factories\PostgraduateRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class PostgraduateRecord extends Model
{
    /** @use HasFactory<PostgraduateRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'record_no', 'student_id', 'program_id', 'degree_code',
        'admission_reference', 'status', 'decision_reference', 'created_by',
    ];

    protected static function booted(): void
    {
        static::updating(function (PostgraduateRecord $record): void {
            if ($record->isDirty(['record_no', 'student_id', 'program_id', 'degree_code'])) {
                throw new LogicException('هوية ملف الدراسات العليا لا تُعدل.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('ملفات الدراسات العليا لا تُحذف.');
        });
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<AcademicProgram, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    /** The researcher may act only on their own file; identity is matched through the person record. */
    public function belongsToUser(User $user): bool
    {
        $student = $this->student;

        return $student !== null
            && $student->person_id !== null
            && (int) $student->person_id === (int) $user->person_id;
    }

    /** @return HasMany<SupervisorAssignment, $this> */
    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(SupervisorAssignment::class);
    }

    /** @return HasMany<ResearchProposal, $this> */
    public function proposals(): HasMany
    {
        return $this->hasMany(ResearchProposal::class);
    }
}
