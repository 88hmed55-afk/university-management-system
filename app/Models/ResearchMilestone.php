<?php

namespace App\Models;

use Database\Factories\ResearchMilestoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ResearchMilestone extends Model
{
    /** @use HasFactory<ResearchMilestoneFactory> */
    use HasFactory;

    protected $fillable = [
        'milestone_no', 'postgraduate_record_id', 'title', 'report_note',
        'due_at', 'submitted_at', 'submitted_by', 'supervisor_evaluation', 'evaluated_by',
        'status', 'decision_reference',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'submitted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (ResearchMilestone $milestone): void {
            if ($milestone->isDirty([
                'milestone_no', 'postgraduate_record_id', 'title', 'due_at',
            ])) {
                throw new LogicException('هوية مرحلة البحث وتاريخها المستحق لا يُعدلان.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('مراحل البحث لا تُحذف.');
        });
    }

    /** @return BelongsTo<PostgraduateRecord, $this> */
    public function record(): BelongsTo
    {
        return $this->belongsTo(PostgraduateRecord::class, 'postgraduate_record_id');
    }
}
