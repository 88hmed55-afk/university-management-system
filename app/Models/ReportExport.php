<?php

namespace App\Models;

use Database\Factories\ReportExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReportExport extends Model
{
    /** @use HasFactory<ReportExportFactory> */
    use HasFactory;

    protected $fillable = [
        'export_no', 'report_definition_id', 'actor_id', 'format_code', 'storage_key',
        'checksum', 'row_count', 'max_rows', 'limit_reference', 'filters',
        'scope_snapshot', 'include_pii', 'pii_basis_reference', 'idempotency_key',
        'status', 'failure_reason', 'correlation_id', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'scope_snapshot' => 'array',
            'include_pii' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('سجلات التصدير غير قابلة للتعديل.');
        });

        static::deleting(function (): never {
            throw new LogicException('سجلات التصدير غير قابلة للحذف.');
        });
    }

    /** @return BelongsTo<ReportDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }
}
