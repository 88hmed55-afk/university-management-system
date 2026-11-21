<?php

namespace App\Models;

use Database\Factories\ResearchProposalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ResearchProposal extends Model
{
    /** @use HasFactory<ResearchProposalFactory> */
    use HasFactory;

    protected $fillable = [
        'proposal_no', 'postgraduate_record_id', 'version_no', 'title',
        'abstract', 'document_id', 'status', 'submitted_at', 'submitted_by', 'approved_by',
        'decision_reference',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (ResearchProposal $proposal): void {
            $locked = in_array($proposal->getOriginal('status'), ['submitted', 'approved'], true);

            if ($locked && $proposal->isDirty([
                'proposal_no', 'postgraduate_record_id', 'version_no',
                'title', 'abstract', 'document_id',
            ])) {
                throw new LogicException('نسخة المقترح المُقدَّمة/المعتمدة لا تُعدل؛ ارفع نسخة جديدة.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('نسخ المقترح لا تُحذف؛ كل نسخة محفوظة.');
        });
    }

    /** @return BelongsTo<PostgraduateRecord, $this> */
    public function record(): BelongsTo
    {
        return $this->belongsTo(PostgraduateRecord::class, 'postgraduate_record_id');
    }
}
