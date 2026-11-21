<?php

namespace App\Actions;

use App\DataScopeCode;
use App\Models\DataScope;
use App\Models\User;
use App\RoleCode;
use App\Support\ReferenceDataCatalog;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

/**
 * Read-only validation of a reference-data import file. It never writes to the
 * target tables; the operator receives a preview and the exact blocking errors.
 */
final class DryRunReferenceImport
{
    /**
     * @param  array<int, mixed>  $rows
     * @return array{set: string, source: string, accepted: array<int, array<string, mixed>>, errors: array<int, string>, would_insert: int, would_skip: int, writes_performed: int}
     */
    public function __invoke(User $actor, string $setCode, array $rows): array
    {
        if (! $actor->hasActiveRole(RoleCode::SystemAdmin)
            && ! $actor->hasActiveRole(RoleCode::IdentityAdmin)) {
            throw new AuthorizationException('فحص استيراد البيانات المرجعية مقصور على مسؤول النظام أو هوية.');
        }

        $set = ReferenceDataCatalog::set($setCode);

        if (! array_is_list($rows)) {
            throw new InvalidArgumentException('ملف الاستيراد يجب أن يحتوي على قائمة صفوف.');
        }

        $errors = [];
        $accepted = [];
        $seen = [];
        $wouldInsert = 0;
        $wouldSkip = 0;
        $model = $set['model'];
        $enum = $set['enum'];

        foreach ($rows as $index => $row) {
            $line = $index + 1;

            if (! is_array($row)) {
                $errors[] = "صف {$line}: الصف ليس كائناً صالحاً.";

                continue;
            }

            $unknown = array_diff(array_keys($row), $set['fields']);

            if ($unknown !== []) {
                $errors[] = "صف {$line}: حقول غير معتمدة في هذه المجموعة (".implode('، ', array_map('strval', $unknown)).').';

                continue;
            }

            $rawCode = $row['code'] ?? null;

            if (! is_scalar($rawCode) || trim((string) $rawCode) === '') {
                $errors[] = "صف {$line}: الحقل code مطلوب.";

                continue;
            }

            $code = trim((string) $rawCode);

            if ($enum::tryFrom($code) === null) {
                $errors[] = "صف {$line}: رمز {$code} غير معتمد في البيانات المرجعية.";

                continue;
            }

            if (isset($seen[$code])) {
                $errors[] = "صف {$line}: الرمز {$code} مكرر داخل الملف (الصف {$seen[$code]}).";

                continue;
            }

            $seen[$code] = $line;
            $normalized = ['code' => $code];
            $rowErrors = [];

            if ($setCode === 'roles') {
                if (array_key_exists('data_scope', $row)) {
                    $scopeCode = $row['data_scope'];

                    if ($scopeCode === null || $scopeCode === '') {
                        $normalized['data_scope_id'] = null;
                    } elseif (! is_string($scopeCode) || DataScopeCode::tryFrom($scopeCode) === null) {
                        $rowErrors[] = "صف {$line}: نطاق البيانات غير معتمد.";
                    } elseif (! DataScope::query()->where('code', $scopeCode)->exists()) {
                        $rowErrors[] = "صف {$line}: نطاق البيانات {$scopeCode} غير موجود؛ استورد نطاقات البيانات أولاً.";
                    } else {
                        $normalized['data_scope'] = $scopeCode;
                    }
                }

                if (array_key_exists('is_system', $row)) {
                    if (! is_bool($row['is_system'])) {
                        $rowErrors[] = "صف {$line}: حقل is_system يجب أن يكون منطقياً.";
                    } else {
                        $normalized['is_system'] = $row['is_system'];
                    }
                }
            }

            if ($rowErrors !== []) {
                $errors = array_merge($errors, $rowErrors);

                continue;
            }

            $exists = $model::query()->where($set['key'], $code)->exists();
            $normalized['action'] = $exists ? 'skip' : 'insert';

            if ($exists) {
                $wouldSkip++;
            } else {
                $wouldInsert++;
            }

            $accepted[] = $normalized;
        }

        return [
            'set' => $setCode,
            'source' => $set['source'],
            'accepted' => $accepted,
            'errors' => $errors,
            'would_insert' => $wouldInsert,
            'would_skip' => $wouldSkip,
            'writes_performed' => 0,
        ];
    }
}
