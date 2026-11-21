<?php

namespace App\Console\Commands;

use App\Support\LaunchReadinessGate;
use Illuminate\Console\Command;

final class LaunchReadinessCommand extends Command
{
    /** @var string */
    protected $signature = 'qa:launch-readiness';

    /** @var string */
    protected $description = 'عرض فحوص جاهزية الإطلاق والقرارات المعلّقة دون إصدار قرار إطلاق';

    public function handle(): int
    {
        $report = (new LaunchReadinessGate)->evaluate();
        $rows = [];

        foreach ($report['checks'] as $check) {
            $rows[] = [$check['key'], $check['ok'] ? 'OK' : 'FAIL', $check['detail']];
        }

        $this->table(['الفحص', 'النتيجة', 'التفصيل'], $rows);

        $blockers = [];

        foreach ($report['open_decisions'] as $decision) {
            $blockers[] = [$decision['id'], $decision['owner'], $decision['source']];
        }

        $this->line('القرارات المعلّقة (بوابة إطلاق):');
        $this->table(['الرقم', 'المالك', 'المرجع'], $blockers);

        $this->line("الحالة: {$report['status']}");
        $this->line($report['summary']);
        $this->warn('هذا التقرير لا يُصدر قرار إطلاق؛ الاعتماد النهائي للجامعة فقط.');

        // The gate never authorises launch: any technical failure or open
        // decision must stop an automated pipeline from treating this as GO.
        return self::FAILURE;
    }
}
