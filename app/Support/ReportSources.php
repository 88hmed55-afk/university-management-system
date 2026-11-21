<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\Employee;
use App\Models\LibraryBranch;
use App\Models\ServiceRequest;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * A closed registry of report sources. Users never supply SQL or table names;
 * every source applies the same scope rule used by its own screen.
 */
final class ReportSources
{
    /** @return array<int, string> */
    public static function codes(): array
    {
        return [
            'campuses',
            'library_branches',
            'students_self',
            'invoices_self',
            'payments_self',
            'service_requests_self',
            'audit_logs',
        ];
    }

    /**
     * @return array{scope: string, filters: array<int, string>, columns: array<int, string>, sensitive: array<int, string>, approved_status: array{0: string, 1: array<int, string>}|null}
     */
    public static function descriptor(string $sourceCode): array
    {
        return match ($sourceCode) {
            'campuses' => [
                'scope' => 'institution',
                'filters' => ['code', 'status'],
                'columns' => ['id', 'code', 'name', 'status'],
                'sensitive' => [],
                'approved_status' => ['campuses.status', ['active']],
            ],
            'library_branches' => [
                'scope' => 'campus',
                'filters' => ['campus_id', 'code', 'status'],
                'columns' => ['id', 'code', 'name', 'campus_id', 'status'],
                'sensitive' => [],
                'approved_status' => ['library_branches.status', ['active']],
            ],
            'students_self' => [
                'scope' => 'self',
                'filters' => ['student_no', 'status'],
                'columns' => ['id', 'student_no', 'given_name', 'family_name', 'status'],
                'sensitive' => ['given_name', 'family_name'],
                'approved_status' => ['students.status', ['active']],
            ],
            'invoices_self' => [
                'scope' => 'self',
                'filters' => ['invoice_no', 'currency_id', 'status'],
                'columns' => ['id', 'invoice_no', 'total_amount', 'currency_id', 'status', 'issued_at'],
                'sensitive' => [],
                'approved_status' => ['student_invoices.status', ['issued']],
            ],
            'payments_self' => [
                'scope' => 'self',
                'filters' => ['payment_no', 'currency_id', 'method_code', 'status'],
                'columns' => ['id', 'payment_no', 'amount', 'currency_id', 'method_code', 'payment_date'],
                'sensitive' => [],
                'approved_status' => ['student_payments.status', ['captured']],
            ],
            'service_requests_self' => [
                'scope' => 'self',
                'filters' => ['service_request_type_id', 'status'],
                'columns' => ['id', 'request_no', 'service_request_type_id', 'status', 'submitted_at', 'closed_at'],
                'sensitive' => [],
                'approved_status' => ['service_requests.status', ['completed']],
            ],
            'audit_logs' => [
                'scope' => 'institution',
                'filters' => ['action', 'outcome', 'actor_id', 'subject_type'],
                'columns' => ['id', 'action', 'subject_type', 'subject_id', 'outcome', 'actor_id', 'created_at'],
                'sensitive' => [],
                'approved_status' => null,
            ],
            default => throw new InvalidArgumentException('مصدر التقرير غير معروف أو غير مسموح.'),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string|int>
     */
    public static function validateFilters(string $sourceCode, array $filters): array
    {
        $allowed = self::descriptor($sourceCode)['filters'];
        $clean = [];

        foreach ($filters as $key => $value) {
            if (! is_string($key) || ! in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('فلتر التقرير غير مسموح لهذا المصدر.');
            }

            if (is_array($value)) {
                $items = array_values($value);

                foreach ($items as $item) {
                    if (! is_scalar($item)) {
                        throw new InvalidArgumentException('قيمة فلتر التقرير غير صالحة.');
                    }
                }

                $clean[$key] = array_map('strval', $items);

                continue;
            }

            if ($value !== null && ! is_scalar($value)) {
                throw new InvalidArgumentException('قيمة فلتر التقرير غير صالحة.');
            }

            if ($value !== null && trim((string) $value) !== '') {
                $clean[$key] = (string) $value;
            }
        }

        return $clean;
    }

    /**
     * @param  array<string, string|int|array<int, string>>  $filters
     * @return Builder<mixed>
     */
    public static function buildQuery(string $sourceCode, User $actor, array $filters): Builder
    {
        $query = match ($sourceCode) {
            'campuses' => Campus::query()
                ->select(['id', 'code', 'name', 'status']),
            'library_branches' => LibraryBranch::query()
                ->select(['id', 'code', 'name', 'campus_id', 'status'])
                ->visibleTo($actor),
            'students_self' => Student::query()
                ->select([
                    'students.id as id', 'students.student_no as student_no',
                    'students.status as status',
                    'people.given_name as given_name', 'people.family_name as family_name',
                ])
                ->join('people', 'people.id', '=', 'students.person_id')
                ->where('students.person_id', (int) $actor->person_id),
            'invoices_self' => StudentInvoice::query()
                ->select([
                    'student_invoices.id as id', 'student_invoices.invoice_no as invoice_no',
                    'student_invoices.total_amount as total_amount', 'student_invoices.currency_id as currency_id',
                    'student_invoices.status as status', 'student_invoices.issued_at as issued_at',
                ])
                ->join('students', 'students.id', '=', 'student_invoices.student_id')
                ->where('students.person_id', (int) $actor->person_id),
            'payments_self' => StudentPayment::query()
                ->select([
                    'student_payments.id as id', 'student_payments.payment_no as payment_no',
                    'student_payments.amount as amount', 'student_payments.currency_id as currency_id',
                    'student_payments.method_code as method_code', 'student_payments.payment_date as payment_date',
                ])
                ->join('students', 'students.id', '=', 'student_payments.student_id')
                ->where('students.person_id', (int) $actor->person_id),
            'service_requests_self' => self::serviceRequestsQuery($actor),
            'audit_logs' => AuditLog::query()
                ->select([
                    'id', 'action', 'subject_type', 'subject_id', 'outcome', 'actor_id', 'created_at',
                ]),
            default => throw new InvalidArgumentException('مصدر التقرير غير معروف أو غير مسموح.'),
        };

        $columnMap = self::filterColumnMap($sourceCode);

        foreach ($filters as $key => $value) {
            $column = $columnMap[$key] ?? null;

            if ($column === null) {
                throw new InvalidArgumentException('فلتر التقرير غير مسموح لهذا المصدر.');
            }

            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    /** @return array<string, string> */
    private static function filterColumnMap(string $sourceCode): array
    {
        $prefix = match ($sourceCode) {
            'students_self' => 'students.',
            'invoices_self' => 'student_invoices.',
            'payments_self' => 'student_payments.',
            default => '',
        };

        $columns = [];

        foreach (self::descriptor($sourceCode)['filters'] as $filter) {
            $columns[$filter] = $prefix.$filter;
        }

        return $columns;
    }

    /** @return Builder<ServiceRequest> */
    private static function serviceRequestsQuery(User $actor): Builder
    {
        $studentIds = Student::query()->where('person_id', $actor->person_id)->pluck('id')->all();
        $employeeIds = Employee::query()->where('person_id', $actor->person_id)->pluck('id')->all();

        return ServiceRequest::query()
            ->select([
                'id', 'request_no', 'service_request_type_id', 'status', 'submitted_at', 'closed_at',
            ])
            ->where(function (Builder $query) use ($studentIds, $employeeIds): void {
                if ($studentIds !== []) {
                    $query->orWhere(function (Builder $inner) use ($studentIds): void {
                        $inner->where('requester_type', Student::class)->whereIn('requester_id', $studentIds);
                    });
                }

                if ($employeeIds !== []) {
                    $query->orWhere(function (Builder $inner) use ($employeeIds): void {
                        $inner->where('requester_type', Employee::class)->whereIn('requester_id', $employeeIds);
                    });
                }

                if ($studentIds === [] && $employeeIds === []) {
                    $query->where('requester_id', -1);
                }
            });
    }
}
