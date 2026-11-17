<?php

namespace Tests\Feature;

use App\Actions\ActivateInstitution;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\RecordStatus;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstitutionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_migrations_create_institution_and_campus_schema(): void
    {
        $this->assertTrue(Schema::hasTable('institutions'));
        $this->assertTrue(Schema::hasTable('institution_settings'));
        $this->assertTrue(Schema::hasTable('campuses'));

        $this->assertTrue(Schema::hasColumns('institutions', [
            'id', 'code', 'name', 'legal_name', 'status', 'archived_at', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('institution_settings', [
            'id', 'institution_id', 'setting_key', 'type', 'value', 'effective_from', 'effective_to', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('campuses', [
            'id', 'institution_id', 'code', 'name', 'address', 'status', 'archived_at', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_new_campus_can_be_added_without_changing_application_code(): void
    {
        $institution = $this->makeInstitution();

        $first = Campus::create([
            'institution_id' => $institution->id,
            'code' => 'CAMPUS-A',
            'name' => 'الحرم الأول',
        ]);
        $second = Campus::create([
            'institution_id' => $institution->id,
            'code' => 'CAMPUS-B',
            'name' => 'الحرم الثاني',
        ]);

        $this->assertDatabaseCount('campuses', 2);
        $this->assertTrue($second->institution->is($institution));

        $codes = Campus::query()
            ->forInstitution($institution->id)
            ->active()
            ->orderBy('code')
            ->pluck('code')
            ->all();

        $this->assertSame(['CAMPUS-A', 'CAMPUS-B'], $codes);
        $this->assertSame(RecordStatus::Active, $first->fresh()->status);
    }

    public function test_campus_code_cannot_be_duplicated(): void
    {
        $institution = $this->makeInstitution();

        Campus::create([
            'institution_id' => $institution->id,
            'code' => 'CAMPUS-A',
            'name' => 'الحرم الأول',
        ]);

        $this->expectException(QueryException::class);

        Campus::create([
            'institution_id' => $institution->id,
            'code' => 'CAMPUS-A',
            'name' => 'حرم مكرر',
        ]);
    }

    public function test_archiving_a_campus_keeps_its_history_and_removes_it_from_active_scope(): void
    {
        $institution = $this->makeInstitution();

        $campus = Campus::create([
            'institution_id' => $institution->id,
            'code' => 'CAMPUS-A',
            'name' => 'الحرم الأول',
        ]);

        $campus->status = RecordStatus::Archived;
        $campus->archived_at = now();
        $campus->save();

        $this->assertDatabaseHas('campuses', ['id' => $campus->id, 'code' => 'CAMPUS-A']);
        $this->assertSame(0, Campus::query()->active()->count());
        $this->assertSame(1, Campus::query()->count());
    }

    public function test_only_one_institution_can_be_active(): void
    {
        $first = $this->makeInstitution('INS-FIRST');
        $second = $this->makeInstitution('INS-SECOND');

        app(ActivateInstitution::class)($second);

        $this->assertSame(RecordStatus::Archived, $first->fresh()->status);
        $this->assertSame(RecordStatus::Active, $second->fresh()->status);
        $this->assertNotNull($first->fresh()->archived_at);
        $this->assertSame(1, Institution::query()->where('status', RecordStatus::Active->value)->count());
    }

    public function test_institution_settings_are_effective_dated_and_unique_per_key_and_date(): void
    {
        $institution = $this->makeInstitution();

        $setting = InstitutionSetting::create([
            'institution_id' => $institution->id,
            'setting_key' => 'academic_calendar_source',
            'type' => 'string',
            'value' => 'registrar',
            'effective_from' => '2026-01-01',
        ]);

        $this->assertSame('2026-01-01', $setting->effective_from->toDateString());
        $this->assertTrue(
            InstitutionSetting::query()
                ->where('institution_id', $institution->id)
                ->where('setting_key', 'academic_calendar_source')
                ->whereDate('effective_from', '2026-01-01')
                ->exists()
        );

        $this->expectException(QueryException::class);

        InstitutionSetting::create([
            'institution_id' => $institution->id,
            'setting_key' => 'academic_calendar_source',
            'type' => 'string',
            'value' => 'duplicate',
            'effective_from' => '2026-01-01',
        ]);
    }

    public function test_status_codes_have_arabic_and_english_labels(): void
    {
        App::setLocale('ar');
        $this->assertSame('نشط', RecordStatus::Active->label());

        App::setLocale('en');
        $this->assertSame('Archived', RecordStatus::Archived->label());
    }

    private function makeInstitution(string $code = 'INS-TEST'): Institution
    {
        return Institution::create([
            'code' => $code,
            'name' => 'جامعة اختبار',
        ]);
    }
}
