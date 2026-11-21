<?php

namespace App\Models;

use Database\Factories\ReportRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReportRun extends Model
{
    /** @use HasFactory<ReportRunFactory> */
    use HasFactory;

    protected $fillable = [
        'run_no', 'report_definition_id', 'actor_id', 'correlation_id', 'filters',
        'scope_snapshot', 'row_count', 'checksum', 'status', 'failure_reason', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'scope_snapshot' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('تشغيلات التقارير غير قابلة للتعديل.');
        });

        static::deleting(function (): never {
            throw new LogicException('تشغيلات التقارير غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<ReportDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }
}
