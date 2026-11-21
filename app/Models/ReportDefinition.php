<?php

namespace App\Models;

use Database\Factories\ReportDefinitionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ReportDefinition extends Model
{
    /** @use HasFactory<ReportDefinitionFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'owner_id', 'source_code', 'metric_definition',
        'metric_reference', 'scope_code', 'allowed_role_codes',
        'allowed_export_role_codes', 'filter_definition', 'max_rows',
        'max_rows_reference', 'freshness_code', 'freshness_reference',
        'include_drafts', 'include_drafts_reference', 'status', 'created_by',
        'approved_by', 'approved_at', 'decision_reference', 'archived_at', 'archive_reason',
    ];

    protected function casts(): array
    {
        return [
            'allowed_role_codes' => 'array',
            'allowed_export_role_codes' => 'array',
            'filter_definition' => 'array',
            'include_drafts' => 'boolean',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ReportDefinition $definition): void {
            if ($definition->isDirty([
                'code', 'source_code', 'metric_definition', 'metric_reference',
                'scope_code', 'allowed_role_codes', 'allowed_export_role_codes',
                'filter_definition', 'max_rows', 'max_rows_reference',
            ])) {
                throw new LogicException('تعريف التقرير غير قابل للتعديل؛ أنشئ تعريفاً جديداً.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('تعريفات التقارير لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @param Builder<ReportDefinition> $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<ReportRun, $this> */
    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class);
    }

    /** @return HasMany<ReportExport, $this> */
    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }
}
