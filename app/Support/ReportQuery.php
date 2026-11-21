<?php

namespace App\Support;

use App\Models\ReportDefinition;
use App\Models\User;
use DateTimeInterface;
use InvalidArgumentException;
use JsonException;

final class ReportQuery
{
    /**
     * The single query engine shared by dashboards, detail runs and exports so
     * that the same actor always sees the same scoped numbers.
     *
     * @param  array<string, mixed>  $filters
     * @return array{rows: array<int, array<string, mixed>>, columns: array<int, string>, scope: array<string, mixed>, filters: array<string, string|int|array<int, string>>, checksum: string}
     */
    public static function execute(
        ReportDefinition $definition,
        User $actor,
        array $filters = [],
        bool $includePii = false,
    ): array {
        $descriptor = ReportSources::descriptor($definition->source_code);

        if (! in_array($definition->scope_code, ReportAccess::supportedScopeCodes(), true)
            || $definition->scope_code !== $descriptor['scope']) {
            throw new InvalidArgumentException('نطاق التقرير لا يطابق مصدر بياناته.');
        }

        if (! ReportAccess::canUseScope($actor, $definition->scope_code)) {
            throw new InvalidArgumentException('لا يمكن تنفيذ التقرير خارج نطاق المستخدم.');
        }

        $cleanFilters = ReportSources::validateFilters($definition->source_code, $filters);
        $query = ReportSources::buildQuery($definition->source_code, $actor, $cleanFilters);

        if (! $definition->include_drafts && $descriptor['approved_status'] !== null) {
            [$column, $statuses] = $descriptor['approved_status'];
            $query->whereIn($column, $statuses);
        }

        $rows = $query->orderBy('id')
            ->limit(max(1, (int) $definition->max_rows))
            ->get()
            ->map(fn ($row): array => self::normalizeRow($row->toArray(), $descriptor['sensitive'], $includePii))
            ->all();

        $scope = self::scopeSnapshot($definition, $actor);

        try {
            $payload = json_encode(
                ['definition' => $definition->code, 'filters' => $cleanFilters, 'scope' => $scope, 'rows' => $rows],
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            throw new InvalidArgumentException('تعذّر حساب بصمة نتيجة التقرير.');
        }

        return [
            'rows' => $rows,
            'columns' => $descriptor['columns'],
            'scope' => $scope,
            'filters' => $cleanFilters,
            'checksum' => hash('sha256', $payload),
        ];
    }

    /** @return array<string, mixed> */
    public static function scopeSnapshot(ReportDefinition $definition, User $actor): array
    {
        return [
            'scope_code' => $definition->scope_code,
            'source_code' => $definition->source_code,
            'institution_wide' => $actor->hasInstitutionWideAccess(),
            'visible_campus_ids' => $actor->visibleCampusIds(),
            'actor_id' => $actor->getKey(),
            'person_bound' => $actor->person_id !== null,
            'include_drafts' => (bool) $definition->include_drafts,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $sensitiveColumns
     * @return array<string, mixed>
     */
    private static function normalizeRow(array $row, array $sensitiveColumns, bool $includePii): array
    {
        foreach ($row as $key => $value) {
            if (in_array($key, $sensitiveColumns, true) && ! $includePii) {
                $row[$key] = '***';

                continue;
            }

            if ($value instanceof DateTimeInterface) {
                $row[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_bool($value)) {
                $row[$key] = $value ? 1 : 0;
            } elseif (is_array($value)) {
                $row[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return $row;
    }
}
