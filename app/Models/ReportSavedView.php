<?php

namespace App\Models;

use Database\Factories\ReportSavedViewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReportSavedView extends Model
{
    /** @use HasFactory<ReportSavedViewFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'report_definition_id', 'actor_id', 'filters', 'scope_snapshot', 'status',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'scope_snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ReportSavedView $view): void {
            if ($view->isDirty(['filters', 'scope_snapshot', 'actor_id', 'report_definition_id'])) {
                throw new LogicException('نطاق العرض المحفوظ غير قابل للتعديل.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('العروض المحفوظة لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @return BelongsTo<ReportDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }
}
