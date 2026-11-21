<?php

namespace Tests\Feature;

use App\RoleCode;
use App\Support\LaunchReadinessGate;
use App\Support\PilotUatCatalog;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PilotAndLaunchRunbookTest extends TestCase
{
    use RefreshDatabase;

    public function test_launch_gate_never_authorises_launch_and_lists_all_open_decisions(): void
    {
        $report = (new LaunchReadinessGate)->evaluate();

        $this->assertContains($report['status'], ['NO_GO', 'PENDING_HUMAN_APPROVAL']);
        $this->assertFalse($report['can_launch'], 'بوابة الإطلاق لا تُصدر قرار GO أبداً.');
        $this->assertSame(
            ['migrations', 'app_key', 'private_storage', 'ci', 'operations_docs'],
            array_column($report['checks'], 'key'),
        );

        $ids = array_column($report['open_decisions'], 'id');
        $this->assertCount(24, $ids);

        for ($number = 1; $number <= 24; $number++) {
            $this->assertContains(sprintf('P-%03d', $number), $ids);
        }

        foreach ($report['open_decisions'] as $decision) {
            $this->assertSame(
                'Documentation/Decisions/PENDING_DECISIONS_REGISTER.md',
                $decision['source'],
            );
            $this->assertNotSame('', $decision['owner']);
        }
    }

    public function test_launch_gate_checks_are_read_only(): void
    {
        $tables = fn (): array => array_map(
            fn (object $row): string => (string) $row->name,
            DB::select("SELECT name FROM sqlite_master WHERE type='table'"),
        );

        $before = $tables();

        (new LaunchReadinessGate)->evaluate();
        (new LaunchReadinessGate)->evaluate();

        $this->assertSame($before, $tables());
    }

    public function test_uat_catalog_is_blueprint_sourced_and_role_based(): void
    {
        $scenarios = PilotUatCatalog::scenarios();

        $this->assertCount(16, $scenarios);
        $this->assertCount(9, PilotUatCatalog::roles());

        foreach ($scenarios as $scenario) {
            $this->assertNotSame('', $scenario['id']);
            $this->assertNotSame('', $scenario['scenario']);
            $this->assertNotSame('', $scenario['expected']);
            $this->assertStringContainsString('UMS-Master-Blueprint-v1.0/docs/', $scenario['source']);
            $this->assertNotNull(RoleCode::tryFrom($scenario['role']));
        }

        $this->assertCount(
            count($scenarios),
            array_unique(array_column($scenarios, 'id')),
        );

        $expectedRoles = [
            RoleCode::UniversityPresident->value,
            RoleCode::AcademicLeadership->value,
            RoleCode::CampusManager->value,
            RoleCode::CollegeDean->value,
            RoleCode::DepartmentHead->value,
            RoleCode::AdmissionsOfficer->value,
            RoleCode::FinanceDirector->value,
            RoleCode::Student->value,
            RoleCode::FacultyMember->value,
        ];

        foreach ($expectedRoles as $role) {
            $this->assertContains($role, PilotUatCatalog::roles());
        }
    }

    public function test_launch_readiness_command_reports_facts_and_never_signals_go(): void
    {
        $exit = Artisan::call('qa:launch-readiness');
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $exit);
        $this->assertTrue(
            str_contains($output, 'NO_GO') || str_contains($output, 'PENDING_HUMAN_APPROVAL'),
            'يجب أن يعرض الأمر إحدى حالتي البوابة المعترف بهما.',
        );
        $this->assertStringContainsString('P-001', $output);
        $this->assertStringContainsString('P-024', $output);
        $this->assertStringContainsString('القرارات المعلّقة', $output);
        $this->assertStringContainsString('لا يُصدر قرار إطلاق', $output);
    }
}
