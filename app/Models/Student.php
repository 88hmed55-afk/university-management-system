<?php

namespace App\Models;

use App\RoleCode;
use App\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected $fillable = [
        'person_id', 'student_no', 'source_application_id', 'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('سجلات الطلاب لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<Student> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', StudentStatus::Active->value);
    }

    /** @param Builder<Student> $query */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! $user->hasAnyActiveRole()) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($user->hasInstitutionWideAccess()
            || $user->hasActiveRole(RoleCode::AdmissionsRegistrationDirector)
            || $user->hasActiveRole(RoleCode::RegistrationOfficer)) {
            return;
        }

        $collegeIds = [];

        if ($user->hasActiveRole(RoleCode::CollegeDean)) {
            $collegeIds = $user->assignedCollegeIds();
        }

        $query->whereHas('enrollments', function (Builder $query) use ($collegeIds): void {
            $query->whereHas('program', function (Builder $query) use ($collegeIds): void {
                $query->whereIn('primary_college_id', $collegeIds);
            });
        });
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function sourceApplication(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'source_application_id');
    }

    /** @return HasMany<StudentProgramEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentProgramEnrollment::class);
    }

    /** @return HasMany<StudentStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class);
    }

    /** @return HasMany<StudentDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }
}
