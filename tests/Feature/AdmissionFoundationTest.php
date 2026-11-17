<?php

namespace Tests\Feature;

use App\Actions\DecideApplication;
use App\Actions\SubmitApplication;
use App\Models\AdmissionCycle;
use App\Models\AdmissionDecision;
use App\Models\AdmissionReview;
use App\Models\ApplicantEvent;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class AdmissionFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_admission_schema_is_created(): void
    {
        foreach (['admission_cycles', 'applications', 'admission_reviews', 'admission_decisions', 'applicant_events'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('applications', [
            'admission_cycle_id', 'person_id', 'application_no', 'program_code',
            'idempotency_key', 'status', 'submitted_at',
        ]));
        $this->assertTrue(Schema::hasColumns('admission_decisions', [
            'application_id', 'decision', 'decided_by', 'decided_at', 'reference',
        ]));
    }

    public function test_cycle_is_dated_and_archived_without_deletion(): void
    {
        $cycle = AdmissionCycle::factory()->create([
            'application_open_at' => '2026-09-01 00:00:00',
            'application_close_at' => '2026-10-01 00:00:00',
        ]);

        $this->assertNotNull($cycle->application_open_at);
        $this->assertTrue(AdmissionCycle::query()->active()->exists());

        try {
            $cycle->delete();
            $this->fail('حذف دورة القبول يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->assertNotNull($cycle->fresh());
    }

    public function test_submitting_the_same_key_twice_creates_one_application(): void
    {
        $cycle = AdmissionCycle::factory()->create();
        $person = Person::factory()->create();

        $first = (new SubmitApplication)($cycle, $person, 'KEY-1', 'PROVISIONAL-PROGRAM');
        $second = (new SubmitApplication)($cycle, $person, 'KEY-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Application::count());
        $this->assertSame(1, ApplicantEvent::count());
        $this->assertTrue($first->person->is($person));
        $this->assertTrue($first->cycle->is($cycle));
        $this->assertSame('PROVISIONAL-PROGRAM', $first->program_code);
    }

    public function test_submit_requires_an_idempotency_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SubmitApplication)(
            AdmissionCycle::factory()->create(),
            Person::factory()->create(),
            '   ',
        );
    }

    public function test_director_can_record_a_decision_with_audit_and_event(): void
    {
        $application = Application::factory()->create();
        $actor = $this->userWithRole(RoleCode::AdmissionsRegistrationDirector);

        $decision = (new DecideApplication)($application, 'rejected', $actor, 'REF-1', 'عدم استكمال المرفقات');

        $this->assertTrue($decision->application->is($application));
        $this->assertSame($actor->id, $decision->decided_by);
        $this->assertNotNull($decision->decided_at);
        $this->assertSame(1, ApplicantEvent::query()->where('event_type', 'decided')->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admission.decision_recorded',
            'actor_id' => $actor->id,
        ]);

        App::setLocale('ar');
        $this->assertSame('مرفوض', $decision->decisionLabel());
        App::setLocale('en');
        $this->assertSame('Rejected', $decision->decisionLabel());
    }

    public function test_non_director_cannot_decide(): void
    {
        $application = Application::factory()->create();
        $actor = $this->userWithRole(RoleCode::CampusManager);

        try {
            (new DecideApplication)($application, 'approved', $actor, 'REF-2');
            $this->fail('يجب رفض قرار غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertSame(0, AdmissionDecision::count());
        $this->assertSame(0, AuditLog::count());
    }

    public function test_decision_value_and_reference_are_validated(): void
    {
        $application = Application::factory()->create();
        $actor = $this->userWithRole(RoleCode::AdmissionsRegistrationDirector);

        try {
            (new DecideApplication)($application, 'unknown', $actor, 'REF-3');
            $this->fail('القرار غير المعروف يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $this->expectException(InvalidArgumentException::class);

        (new DecideApplication)($application, 'approved', $actor, '   ');
    }

    public function test_reviews_are_allowed_but_decisions_and_events_are_immutable(): void
    {
        $review = AdmissionReview::factory()->create(['outcome' => 'under_review']);
        $decision = AdmissionDecision::factory()->create();
        $event = ApplicantEvent::factory()->create();

        $review->update(['notes' => 'مراجعة']);
        $this->assertSame('مراجعة', $review->fresh()->notes);

        foreach ([$decision, $event] as $row) {
            try {
                $row->delete();
                $this->fail('حذف سجلات القبول الحساسة يجب أن يُرفض.');
            } catch (LogicException) {
                // متوقع
            }
        }

        $this->expectException(LogicException::class);

        $decision->update(['decision' => 'rejected']);
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
