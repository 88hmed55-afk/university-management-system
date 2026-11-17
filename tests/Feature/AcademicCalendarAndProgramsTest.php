<?php

namespace Tests\Feature;

use App\Actions\CreateAcademicTerm;
use App\Actions\DefineProgram;
use App\Models\AcademicProgram;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\ProgramCollegeParticipation;
use App\Models\ProgramVersion;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class AcademicCalendarAndProgramsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_calendar_and_program_schema_is_created(): void
    {
        foreach ([
            'academic_years', 'academic_terms', 'academic_programs',
            'program_college_participations', 'program_versions',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('academic_years', [
            'code', 'name', 'start_date', 'end_date', 'status', 'approved_by', 'decision_reference',
        ]));
        $this->assertTrue(Schema::hasColumns('academic_programs', [
            'code', 'name', 'level_code', 'primary_college_id', 'status',
        ]));
        $this->assertTrue(Schema::hasColumns('program_versions', [
            'academic_program_id', 'version_no', 'approved_at', 'effective_from', 'status',
        ]));
    }

    public function test_academic_years_cannot_overlap_or_be_deleted(): void
    {
        AcademicYear::factory()->create([
            'start_date' => '2040-01-01', 'end_date' => '2040-12-31',
        ]);

        try {
            AcademicYear::factory()->create([
                'start_date' => '2040-06-01', 'end_date' => '2041-12-31',
                'code' => 'AY-OVERLAP',
            ]);
            $this->fail('تداخل السنوات يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $year = AcademicYear::firstOrFail();

        try {
            $year->delete();
            $this->fail('حذف السنة الأكاديمية يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->assertNotNull($year->fresh());
    }

    public function test_academic_year_rejects_inverted_window(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AcademicYear::factory()->create([
            'start_date' => '2040-12-31', 'end_date' => '2040-01-01',
        ]);
    }

    public function test_term_must_be_within_year_and_not_overlap_siblings(): void
    {
        $year = AcademicYear::factory()->create([
            'start_date' => '2040-01-01', 'end_date' => '2040-12-31',
        ]);
        $actor = $this->userWithRole(RoleCode::AcademicLeadership);

        $term = (new CreateAcademicTerm)(
            $year, $actor, 'T1', 'الفصل الأول',
            Carbon::parse('2040-01-01'), Carbon::parse('2040-06-30'), 'DEC-1',
        );

        $this->assertTrue($term->year->is($year));
        $this->assertSame($actor->id, $term->approved_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic.term_created', 'actor_id' => $actor->id,
        ]);

        try {
            (new CreateAcademicTerm)(
                $year, $actor, 'T2', 'الفصل الثاني',
                Carbon::parse('2040-05-01'), Carbon::parse('2040-12-01'), 'DEC-2',
            );
            $this->fail('تداخل الفصول يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $this->expectException(InvalidArgumentException::class);

        (new CreateAcademicTerm)(
            $year, $actor, 'T3', 'خارج السنة',
            Carbon::parse('2039-01-01'), Carbon::parse('2039-06-01'), 'DEC-3',
        );
    }

    public function test_non_academic_leadership_cannot_create_a_term(): void
    {
        $year = AcademicYear::factory()->create();
        $actor = $this->userWithRole(RoleCode::CampusManager);

        try {
            (new CreateAcademicTerm)(
                $year, $actor, 'T1', 'الفصل',
                Carbon::parse('2040-01-01'), Carbon::parse('2040-06-30'), 'DEC-NO',
            );
            $this->fail('يجب رفض إنشاء فصل من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertSame(0, AcademicTerm::count());
        $this->assertSame(0, AuditLog::count());
    }

    public function test_term_requires_a_decision_reference(): void
    {
        $year = AcademicYear::factory()->create();
        $actor = $this->userWithRole(RoleCode::AcademicLeadership);

        $this->expectException(InvalidArgumentException::class);

        (new CreateAcademicTerm)(
            $year, $actor, 'T1', 'الفصل',
            Carbon::parse('2040-01-01'), Carbon::parse('2040-06-30'), '   ',
        );
    }

    public function test_program_has_one_primary_college_and_is_not_deletable(): void
    {
        $college = College::factory()->create();
        $actor = $this->userWithRole(RoleCode::AcademicLeadership);

        $program = (new DefineProgram)($actor, 'PROG-X', 'برنامج تجريبي', $college);

        $this->assertTrue($program->primaryCollege->is($college));
        $this->assertNull($program->level_code);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic.program_defined', 'actor_id' => $actor->id,
        ]);

        try {
            $program->delete();
            $this->fail('حذف البرنامج يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->assertNotNull($program->fresh());
    }

    public function test_non_academic_leadership_cannot_define_a_program(): void
    {
        $college = College::factory()->create();
        $actor = $this->userWithRole(RoleCode::HrOfficer);

        try {
            (new DefineProgram)($actor, 'PROG-NO', 'برنامج', $college);
            $this->fail('يجب رفض تعريف برنامج من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertSame(0, AcademicProgram::count());
    }

    public function test_participation_is_dated_excludes_primary_college_and_is_not_deletable(): void
    {
        $primary = College::factory()->create();
        $participating = College::factory()->create();
        $program = AcademicProgram::factory()->create(['primary_college_id' => $primary->id]);

        $participation = ProgramCollegeParticipation::factory()->create([
            'academic_program_id' => $program->id,
            'college_id' => $participating->id,
            'valid_from' => '2040-01-01',
        ]);

        $this->assertTrue($participation->college->is($participating));
        $this->assertTrue($participation->program->is($program));

        try {
            ProgramCollegeParticipation::factory()->create([
                'academic_program_id' => $program->id,
                'college_id' => $primary->id,
                'valid_from' => '2041-01-01',
            ]);
            $this->fail('الكلية الرئيسية لا تُسجَّل ككلية مشاركة.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $participation->delete();
            $this->fail('حذف المشاركة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->assertNotNull($participation->fresh());
    }

    public function test_program_version_is_unique_per_program_and_approved_one_is_immutable(): void
    {
        $program = AcademicProgram::factory()->create();
        ProgramVersion::factory()->create([
            'academic_program_id' => $program->id, 'version_no' => 1,
        ]);

        try {
            ProgramVersion::factory()->create([
                'academic_program_id' => $program->id, 'version_no' => 1,
            ]);
            $this->fail('رقم إصدار مكرر للبرنامج يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        $approved = ProgramVersion::factory()->create([
            'academic_program_id' => $program->id,
            'version_no' => 2,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->assertSame(1, ProgramVersion::query()->approved()->count());

        try {
            $approved->update(['version_no' => 3]);
            $this->fail('تعديل نسخة معتمدة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $approved->delete();
            $this->fail('حذف نسخة البرنامج يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
