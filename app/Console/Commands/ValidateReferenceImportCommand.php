<?php

namespace App\Console\Commands;

use App\Actions\DryRunReferenceImport;
use App\Models\User;
use App\RoleCode;
use App\Support\ReferenceDataCatalog;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;
use Throwable;

final class ValidateReferenceImportCommand extends Command
{
    /** @var string */
    protected $signature = 'qa:validate-reference-import {file} {--set=}';

    /** @var string */
    protected $description = 'التحقق الجاف من ملف بيانات مرجعية معتمدة دون أي كتابة في قاعدة البيانات';

    public function handle(): int
    {
        $setCode = (string) $this->option('set');

        if ($setCode === '') {
            $this->error('حدد مجموعة البيانات المرجعية باستخدام --set=data_scopes أو --set=roles.');

            return self::FAILURE;
        }

        try {
            $set = ReferenceDataCatalog::set($setCode);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $path = (string) $this->argument('file');

        if (! is_file($path)) {
            $this->error('ملف الاستيراد غير موجود.');

            return self::FAILURE;
        }

        try {
            $contents = file_get_contents($path);
            $rows = json_decode($contents === false ? '' : $contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->error('ملف الاستيراد ليس JSON صالحاً.');

            return self::FAILURE;
        }

        if (! is_array($rows) || ! array_is_list($rows)) {
            $this->error('ملف الاستيراد يجب أن يحتوي على قائمة صفوف.');

            return self::FAILURE;
        }

        $actor = User::query()
            ->whereHas('roles', fn ($query) => $query->where('code', RoleCode::SystemAdmin->value))
            ->first();

        if (! $actor instanceof User) {
            $this->error('لا يوجد مسؤول نظام نشط لتنفيذ فحص الاستيراد.');

            return self::FAILURE;
        }

        try {
            $result = (new DryRunReferenceImport)($actor, $setCode, $rows);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("المجموعة: {$set['label']} — المرجع: {$set['source']}");
        $preview = [];

        foreach ($result['accepted'] as $row) {
            $preview[] = [$row['code'], $row['action'], $row['data_scope'] ?? '—'];
        }

        if ($preview !== []) {
            $this->table(['الرمز', 'الإجراء المقترح', 'النطاق'], $preview);
        }

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        $this->line(
            "الإدراج المقترح: {$result['would_insert']} | التخطي المطابق: {$result['would_skip']} | "
            ."الكتابات المنفذة: {$result['writes_performed']}",
        );

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
