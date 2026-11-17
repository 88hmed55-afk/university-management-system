<?php

namespace App\Models;

use Database\Factories\StudentDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class StudentDocument extends Model
{
    /** @use HasFactory<StudentDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id', 'document_id', 'type_code', 'issue_date', 'expiry_date',
        'verified_at', 'verified_by', 'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('وثائق الطلاب لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
