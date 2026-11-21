<?php

namespace App\Models;

use Database\Factories\ReportDashboardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReportDashboard extends Model
{
    /** @use HasFactory<ReportDashboardFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'role_code', 'scope_code', 'definition_ids',
        'decision_reference', 'status', 'created_by', 'archived_at', 'archive_reason',
    ];

    protected function casts(): array
    {
        return [
            'definition_ids' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ReportDashboard $dashboard): void {
            if ($dashboard->isDirty(['code', 'role_code', 'scope_code', 'definition_ids'])) {
                throw new LogicException('تعريف اللوحة غير قابل للتعديل؛ أنشئ لوحة جديدة.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('لوحات التقارير لا تُحذف؛ تُؤرشف.');
        });
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
