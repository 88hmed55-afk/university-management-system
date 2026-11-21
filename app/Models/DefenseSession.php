<?php

namespace App\Models;

use Database\Factories\DefenseSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class DefenseSession extends Model
{
    /** @use HasFactory<DefenseSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'session_no', 'postgraduate_record_id', 'research_proposal_id',
        'scheduled_at', 'location', 'committee_reference', 'minutes_document_id',
        'recommendation', 'requirements_reference', 'status',
        'acted_by', 'decision_reference',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (DefenseSession $session): void {
            if ($session->getOriginal('status') === 'completed'
                && $session->isDirty(['session_no', 'postgraduate_record_id', 'scheduled_at', 'requirements_reference'])) {
                throw new LogicException('جلسة المناقشة المكتملة لا تُعدل.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('جلسات المناقشة لا تُحذف.');
        });
    }

    /** @return BelongsTo<PostgraduateRecord, $this> */
    public function record(): BelongsTo
    {
        return $this->belongsTo(PostgraduateRecord::class, 'postgraduate_record_id');
    }
}
