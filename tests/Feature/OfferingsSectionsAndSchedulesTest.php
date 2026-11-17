<?php

namespace Tests\Feature;

use App\Actions\AssignInstructor;
use App\Actions\CreateCourseSection;
use App\Actions\OfferCourse;
use App\Actions\ScheduleSectionMeeting;
use App\Models\AcademicTerm;
use App\Models\Campus;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseSection;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Room;
use App\Models\SectionMeeting;
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

class OfferingsSectionsAndSchedulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_offerings_schema_is_created(): void
    {
        foreach (['course_offerings', 'course_sections', 'section_meetings', 'teaching_assignments'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('section_meetings', [
            'course_section_id', 'day_of_week', 'start_time', 'end_time', 'room_id', 'valid_from',
        ]));
        $this->assertTrue(Schema::hasColumns('teaching_assignments', [
            'course_section_id', 'employee_id', 'valid_from', 'valid_to', 'decision_reference',
        ]));
    }

    public function test_offering_is_unique_per_course_and_term_and_authorized(): void
    {
        $course = Course::factory()->create();
        $term = AcademicTerm::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);

        $offering = (new OfferCourse)($course, $term, $actor, 'OFFER-1');

        $this->assertTrue($offering->course->is($course));
        $this->assertTrue($offering->term->is($term));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic.course_offered', 'actor_id' => $actor->id,
        ]);

        try {
            CourseOffering::factory()->create([
                'course_id' => $course->id, 'academic_term_id' => $term->id,
            ]);
            $this->fail('تكرار الطرح يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        $outsider = $this->userWithRole(RoleCode::CampusManager);

        try {
            (new OfferCourse)(
                Course::factory()->create(), AcademicTerm::factory()->create(), $outsider, 'OFFER-NO',
            );
            $this->fail('يجب رفض الطرح من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }
    }

    public function test_section_code_is_unique_per_offering_without_invented_capacity(): void
    {
        $offering = CourseOffering::factory()->create();
        $actor = $this->userWithRole(RoleCode::RegistrationOfficer);
        $campus = Campus::factory()->create();

        $section = (new CreateCourseSection)($offering, $actor, 'SEC-A', null, $campus);

        $this->assertNull($section->capacity);
        $this->assertTrue($section->campus->is($campus));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic.section_created', 'actor_id' => $actor->id,
        ]);

        try {
            CourseSection::factory()->create([
                'course_offering_id' => $offering->id, 'code' => 'SEC-A',
            ]);
            $this->fail('تكرار رمز الشعبة يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        try {
            $section->delete();
            $this->fail('حذف الشعبة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_meetings_reject_bad_times_and_conflicts(): void
    {
        $section = CourseSection::factory()->create();
        $room = Room::factory()->create();
        $actor = $this->userWithRole(RoleCode::FacilitiesOfficer);
        $schedule = new ScheduleSectionMeeting;

        $meeting = $schedule(
            $section, $actor, 1, '08:00', '09:30',
            Carbon::parse('2040-01-01'), null, $room,
        );

        $this->assertTrue($meeting->section->is($section));
        $this->assertTrue($meeting->room->is($room));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic.section_meeting_scheduled', 'actor_id' => $actor->id,
        ]);

        try {
            $schedule(
                $section, $actor, 1, '09:00', '10:00',
                Carbon::parse('2040-01-01'), null, $room,
            );
            $this->fail('تعارض الاجتماع/القاعة يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $schedule(
                $section, $actor, 1, '10:00', '09:00',
                Carbon::parse('2040-01-01'),
            );
            $this->fail('الوقت المعكوس يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $schedule(
                $section, $actor, 9, '10:00', '11:00',
                Carbon::parse('2040-01-01'),
            );
            $this->fail('يوم الأسبوع خارج النطاق يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $meeting->delete();
            $this->fail('حذف الاجتماع يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_meeting_scheduling_is_authorized(): void
    {
        $section = CourseSection::factory()->create();
        $outsider = $this->userWithRole(RoleCode::HrOfficer);

        try {
            (new ScheduleSectionMeeting)(
                $section, $outsider, 1, '08:00', '09:30', Carbon::parse('2040-01-01'),
            );
            $this->fail('يجب رفض جدولة من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertSame(0, SectionMeeting::count());
    }

    public function test_instructor_assignment_rejects_duplicates_and_is_audited(): void
    {
        $section = CourseSection::factory()->create();
        $instructor = Employee::factory()->create();
        $actor = $this->userWithRole(RoleCode::DepartmentHead);
        $assign = new AssignInstructor;

        $assignment = $assign(
            $section, $instructor, $actor, 'TA-1', Carbon::parse('2040-01-01'),
        );

        $this->assertTrue($assignment->employee->is($instructor));
        $this->assertTrue($assignment->section->is($section));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'academic.instructor_assigned', 'actor_id' => $actor->id,
        ]);

        try {
            $assign($section, $instructor, $actor, 'TA-2', Carbon::parse('2040-03-01'));
            $this->fail('تكليف مكرر للمدرس في الشعبة يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $assignment->delete();
            $this->fail('حذف تكليف التدريس يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_section_visibility_is_campus_scoped(): void
    {
        $owned = Campus::factory()->create();
        $other = Campus::factory()->create();
        $inScope = CourseSection::factory()->create(['campus_id' => $owned->id]);
        CourseSection::factory()->create(['campus_id' => $other->id]);

        $manager = $this->userWithRole(RoleCode::CampusManager);
        $manager->campuses()->attach($owned);

        $this->assertSame([$inScope->id], CourseSection::query()->visibleTo($manager->refresh())->pluck('id')->all());
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
