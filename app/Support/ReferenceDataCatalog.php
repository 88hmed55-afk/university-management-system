<?php

namespace App\Support;

use App\DataScopeCode;
use App\Models\DataScope;
use App\Models\Role;
use App\RoleCode;
use InvalidArgumentException;

/**
 * Reference data that already exists in the approved blueprint. This catalog
 * is deliberately closed: no academic, financial or policy list is invented.
 */
final class ReferenceDataCatalog
{
    /**
     * @return array<string, array{label: string, source: string, model: class-string, key: string, fields: array<int, string>, enum: class-string}>
     */
    public static function sets(): array
    {
        return [
            'data_scopes' => [
                'label' => 'نطاقات البيانات',
                'source' => 'UMS-Master-Blueprint-v1.0/docs/05_roles_permissions.md',
                'model' => DataScope::class,
                'key' => 'code',
                'fields' => ['code'],
                'enum' => DataScopeCode::class,
            ],
            'roles' => [
                'label' => 'الأدوار المعتمدة',
                'source' => 'UMS-Master-Blueprint-v1.0/docs/05_roles_permissions.md',
                'model' => Role::class,
                'key' => 'code',
                'fields' => ['code', 'data_scope', 'is_system'],
                'enum' => RoleCode::class,
            ],
        ];
    }

    /**
     * @return array{label: string, source: string, model: class-string, key: string, fields: array<int, string>, enum: class-string}
     */
    public static function set(string $code): array
    {
        $set = self::sets()[$code] ?? null;

        if ($set === null) {
            throw new InvalidArgumentException('مجموعة البيانات المرجعية غير معتمدة في هذا النظام.');
        }

        return $set;
    }

    /**
     * Names and counts come from the blueprint enums, never from user input.
     *
     * @return array<string, int>
     */
    public static function expectedCounts(): array
    {
        return [
            'data_scopes' => count(DataScopeCode::cases()),
            'roles' => count(RoleCode::cases()),
        ];
    }
}
