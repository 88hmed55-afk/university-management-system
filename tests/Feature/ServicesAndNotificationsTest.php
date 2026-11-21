<?php

namespace Tests\Feature;

use App\Actions\ActivateScheduledAnnouncement;
use App\Actions\ApproveNotificationTemplate;
use App\Actions\CreateAnnouncement;
use App\Actions\DefineNotificationTemplate;
use App\Actions\DefineServiceRequestType;
use App\Actions\DefineWorkflowVersion;
use App\Actions\MarkNotificationRead;
use App\Actions\PublishAnnouncement;
use App\Actions\PublishNotificationTemplate;
use App\Actions\PublishWorkflowVersion;
use App\Actions\RecordDeliveryAttempt;
use App\Actions\SendNotification;
use App\Actions\StopAnnouncement;
use App\Actions\SubmitServiceRequest;
use App\Actions\TransitionServiceRequest;
use App\Actions\WithdrawServiceRequest;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\DeliveryAttempt;
use App\Models\Notification;
use App\Models\RequestEvent;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\WorkflowStep;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class ServicesAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_services_schema_is_created(): void
    {
        foreach ([
            'service_request_types', 'service_requests', 'workflow_steps', 'request_events',
            'notification_templates', 'notifications', 'delivery_attempts', 'announcements',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('service_requests', [
            'request_no', 'service_request_type_id', 'requester_type', 'requester_id',
            'status', 'workflow_version_no', 'current_step_no', 'idempotency_key',
        ]));
        $this->assertTrue(Schema::hasColumns('notifications', [
            'notification_no', 'recipient_id', 'notification_template_id',
            'channel_code', 'status', 'read_at',
        ]));
        $this->assertTrue(Schema::hasColumns('announcements', [
            'announcement_no', 'audience_scope_code', 'scope_key', 'status', 'published_at',
        ]));
    }

    public function test_request_type_and_workflow_versions_are_documented_and_immutable(): void
    {
        $director = $this->userWithRole(RoleCode::AdmissionsRegistrationDirector);
        $outsider = $this->userWithRole(RoleCode::CampusManager);
        $defineType = new DefineServiceRequestType;

        try {
            $defineType($outsider, 'SVC-X', 'خدمة');
            $this->fail('تعريف نوع الطلب من غير مخولة يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $type = $defineType($director, 'SVC-T1', 'خدمة اختبار');
        $defineWorkflow = new DefineWorkflowVersion;
        $steps = [
            ['step_no' => 1, 'name' => 'استلام', 'role_code' => 'registration_officer', 'scope_code' => 'campus'],
            ['step_no' => 2, 'name' => 'اعتماد', 'role_code' => 'admissions_registration_director', 'scope_code' => 'institution'],
        ];

        try {
            $defineWorkflow($type, $director, 1, [
                ['step_no' => 1, 'name' => 'خطوة', 'role_code' => 'unknown_role', 'scope_code' => 'campus'],
            ]);
            $this->fail('دور غير معروف في المسار يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $created = $defineWorkflow($type, $director, 1, $steps);
        $this->assertCount(2, $created);
        $this->assertDatabaseHas('audit_logs', ['action' => 'services.workflow_version_defined', 'actor_id' => $director->id]);

        try {
            (new PublishWorkflowVersion)($type, $director, 1, '');
            $this->fail('نشر مسار بلا مرجع قرار يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $published = (new PublishWorkflowVersion)($type, $director, 1, 'DEC-1');
        $this->assertSame('published', $published->first()->status);
        $this->assertSame(1, (int) $type->fresh()->current_workflow_version_no);

        try {
            $published->first()->update(['role_code' => 'cashier']);
            $this->fail('تعديل خطوة منشورة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $defineWorkflow($type, $director, 2, $steps);
        (new PublishWorkflowVersion)($type, $director, 2, 'DEC-2');
        $student = Student::factory()->create();
        (new SubmitServiceRequest)($type, $student, $director, 'وصف', 'REQ-W1');

        $defineWorkflow($type, $director, 3, $steps);

        try {
            (new PublishWorkflowVersion)($type, $director, 3, 'DEC-3');
            $this->fail('تغيير المسار مع طلبات جارية بلا مرجع ترحيل يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        (new PublishWorkflowVersion)($type, $director, 3, 'DEC-3', 'MIG-1');
        $this->assertSame(3, (int) $type->fresh()->current_workflow_version_no);
        $this->assertDatabaseHas('audit_logs', ['action' => 'services.workflow_version_published', 'actor_id' => $director->id]);
        $this->assertSame(3, WorkflowStep::query()->distinct()->count('version_no'));
    }

    public function test_submission_is_idempotent_logged_and_withdrawable_before_processing(): void
    {
        $director = $this->userWithRole(RoleCode::AdmissionsRegistrationDirector);
        $student = Student::factory()->create();
        $type = (new DefineServiceRequestType)($director, 'SVC-T2', 'خدمة اختبار');
        $submit = new SubmitServiceRequest;

        $request = $submit($type, $student, $director, 'وصف الطلب', 'REQ-1', 'IDEM-S1');

        $this->assertSame('submitted', $request->status);
        $this->assertSame(Student::class, $request->requester_type);
        $this->assertSame(1, RequestEvent::query()->where('service_request_id', $request->id)->where('event_type', 'submitted')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'services.request_submitted', 'actor_id' => $director->id]);

        $again = $submit($type, $student, $director, 'وصف آخر', 'REQ-2', 'IDEM-S1');
        $this->assertSame($request->id, $again->id);

        $unauthorizedActor = $this->userWithRole(RoleCode::Student);

        try {
            $submit($type, Student::factory()->create(), $unauthorizedActor, 'وصف', 'REQ-3');
            $this->fail('تقديم طلب عن طالب آخر من غير مخولة يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        try {
            (new WithdrawServiceRequest)($request, $director, '');
            $this->fail('سحب بلا سبب يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $withdrawn = (new WithdrawServiceRequest)($request, $director, 'تكرار غير مقصود');
        $this->assertSame('withdrawn', $withdrawn->status);
        $this->assertSame(2, RequestEvent::query()->where('service_request_id', $request->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'services.request_withdrawn', 'reason' => 'تكرار غير مقصود']);

        try {
            $request->delete();
            $this->fail('حذف الطلب يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $request->update(['description' => 'تعديل']);
            $this->fail('تعديل هوية الطلب يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_workflow_transition_enforces_role_scope_and_reasons(): void
    {
        $director = $this->userWithRole(RoleCode::AdmissionsRegistrationDirector);
        $officer = $this->userWithRole(RoleCode::RegistrationOfficer);
        $officer->campuses()->attach(Campus::factory()->create());
        $facilities = $this->userWithRole(RoleCode::FacilitiesOfficer);
        $type = (new DefineServiceRequestType)($director, 'SVC-T3', 'خدمة اختبار');

        (new DefineWorkflowVersion)($type, $director, 1, [
            ['step_no' => 1, 'name' => 'استلام', 'role_code' => 'registration_officer', 'scope_code' => 'campus'],
            ['step_no' => 2, 'name' => 'اعتماد', 'role_code' => 'admissions_registration_director', 'scope_code' => 'institution'],
        ]);
        (new PublishWorkflowVersion)($type, $director, 1, 'DEC-1');

        $request = (new SubmitServiceRequest)(
            $type, Student::factory()->create(), $director, 'وصف', 'REQ-4',
        );
        $transition = new TransitionServiceRequest;

        try {
            $transition($request, $facilities, 'advance', 'D-1');
            $this->fail('دور غير مطابق لخطوة المسار يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $inReview = $transition($request, $officer, 'advance', 'D-1');
        $this->assertSame('in_review', $inReview->status);
        $this->assertSame(2, (int) $inReview->current_step_no);

        try {
            $transition($inReview, $officer, 'advance', 'D-2');
            $this->fail('تجاوز خطوة المسار التالية من دور غير مخولة يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        try {
            $transition($inReview, $director, 'reject', 'D-3');
            $this->fail('رفض بلا سبب يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $completed = $transition($inReview, $director, 'advance', 'D-3');
        $this->assertSame('completed', $completed->status);
        $this->assertNotNull($completed->closed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'services.request_transitioned', 'actor_id' => $director->id]);
        $this->assertSame(
            ['submitted', 'advanced', 'completed'],
            RequestEvent::query()->where('service_request_id', $request->id)->orderBy('id')->pluck('event_type')->all(),
        );

        try {
            $transition($completed, $director, 'advance', 'D-4');
            $this->fail('معالجة طلب منجز يجب أن تُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }
    }

    public function test_notification_template_lifecycle_enforces_separation_and_immutability(): void
    {
        $creator = $this->userWithRole(RoleCode::AdmissionsRegistrationDirector);
        $approver = $this->userWithRole(RoleCode::UniversityPresident);
        $define = new DefineNotificationTemplate;

        $template = $define($creator, 'TMPL-A', 1, 'internal', 'عنوان', 'نص القالب');

        try {
            $define($creator, 'TMPL-A', 1, 'internal', 'عنوان', 'نص');
            $this->fail('تكرار إصدار القالب يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            (new ApproveNotificationTemplate)($template, $creator, 'D-1');
            $this->fail('اعتماد القالب من منشئه يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        (new ApproveNotificationTemplate)($template, $approver, 'D-1');
        $published = (new PublishNotificationTemplate)($template, $approver, 'D-2');
        $this->assertSame('published', $published->status);

        try {
            $published->update(['body' => 'تعديل']);
            $this->fail('تعديل قالب منشور يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $published->delete();
            $this->fail('حذف قالب يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_notification_send_is_idempotent_scoped_and_read_only_by_recipient(): void
    {
        $creator = $this->userWithRole(RoleCode::AdmissionsRegistrationDirector);
        $approver = $this->userWithRole(RoleCode::UniversityPresident);
        $sender = $this->userWithRole(RoleCode::RegistrationOfficer);
        $recipient = $this->userWithRole(RoleCode::Student);
        $other = $this->userWithRole(RoleCode::Student);

        $template = (new DefineNotificationTemplate)($creator, 'TMPL-N', 1, 'internal', 'عنوان معتمد', 'نص معتمد');
        (new ApproveNotificationTemplate)($template, $approver, 'D-1');
        (new PublishNotificationTemplate)($template, $approver, 'D-2');

        $send = new SendNotification;
        $notification = $send($sender, $recipient, 'NTF-1', $template, null, null, null, null, 'IDEM-N1');

        $this->assertSame('عنوان معتمد', $notification->subject);
        $this->assertSame('internal', $notification->channel_code);
        $this->assertSame($template->id, $notification->notification_template_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'services.notification_sent', 'actor_id' => $sender->id]);

        $again = $send($sender, $recipient, 'NTF-2', $template, null, null, null, null, 'IDEM-N1');
        $this->assertSame($notification->id, $again->id);

        try {
            $send($sender, $recipient, 'NTF-3', $template, 'عنوان مخصص', 'نص');
            $this->fail('تجاوز محتوى القالب المنشور يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $send($sender, User::factory()->create(), 'NTF-4', null, 'عنوان', 'نص');
            $this->fail('إرسال إشعار لمستخدم بلا دور نشط يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $send($sender, $other, 'NTF-5', null, 'عنوان', 'نص', 'self');
            $this->fail('إشعار النطاق الذاتي لغير صاحبه يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        try {
            (new MarkNotificationRead)($notification, $other);
            $this->fail('قراءة إشعار غير مخصص للمستخدم يجب أن تُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $read = (new MarkNotificationRead)($notification, $recipient);
        $this->assertSame('read', $read->status);
        $this->assertNotNull($read->read_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'services.notification_read', 'actor_id' => $recipient->id]);

        try {
            $notification->update(['body' => 'تعديل']);
            $this->fail('تعديل محتوى الإشعار يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_delivery_attempts_are_append_only_retry_safe_and_provider_locked(): void
    {
        $sender = $this->userWithRole(RoleCode::RegistrationOfficer);
        $notification = Notification::factory()->create();
        $record = new RecordDeliveryAttempt;

        $first = $record($notification, $sender, 1, 'success', null, null, 'IDEM-D1');
        $this->assertSame('internal', $first->channel_code);

        $again = $record($notification, $sender, 1, 'success', null, null, 'IDEM-D1');
        $this->assertSame($first->id, $again->id);

        $second = $record($notification, $sender, 2, 'failed', 'تعذر الاتصال', null, 'IDEM-D2');
        $this->assertSame('failed', $second->status);

        try {
            $record($notification, $sender, 2, 'failed', 'خطأ', null, 'IDEM-D3');
            $this->fail('تكرار رقم المحاولة يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $record($notification, $sender, 3, 'success', null, 'sms-provider');
            $this->fail('استخدام مزود خارجي قبل اعتماده يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $record($notification, $sender, 3, 'failed');
            $this->fail('فشل بلا سبب يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $this->assertSame(2, DeliveryAttempt::query()->where('notification_id', $notification->id)->count());

        try {
            $first->delete();
            $this->fail('حذف محاولة الإرسال يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_announcement_lifecycle_enforces_audience_and_separation(): void
    {
        $creator = $this->userWithRole(RoleCode::AdmissionsRegistrationDirector);
        $publisher = $this->userWithRole(RoleCode::UniversityPresident);
        $outsider = $this->userWithRole(RoleCode::FacilitiesOfficer);
        $create = new CreateAnnouncement;

        try {
            $create($outsider, 'ANN-X', 'عنوان', 'نص', 'ar', 'institution');
            $this->fail('إنشاء إعلان من غير مخولة يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        try {
            $create($creator, 'ANN-Y', 'عنوان', 'نص', 'ar', 'campus');
            $this->fail('إعلان بنطاق محدد بلا مفتاح نطاق يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $create($creator, 'ANN-Z', 'عنوان', 'نص', 'fr', 'institution');
            $this->fail('لغة غير مدعومة يجب أن تُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $announcement = $create($creator, 'ANN-1', 'عنوان الإعلان', 'نص الإعلان', 'ar', 'institution');

        try {
            (new PublishAnnouncement)($announcement, $creator, 'D-1');
            $this->fail('نشر الإعلان من منشئه يجب أن يُرفض.');
        } catch (AuthorizationException) {
            // متوقع
        }

        try {
            (new PublishAnnouncement)($announcement, $publisher, '');
            $this->fail('نشر بلا مرجع قرار يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $published = (new PublishAnnouncement)($announcement, $publisher, 'D-1');
        $this->assertSame('published', $published->status);

        try {
            $published->update(['title' => 'تعديل']);
            $this->fail('تعديل إعلان منشور يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $published->refresh();

        try {
            (new StopAnnouncement)($published, $publisher, '', 'D-2');
            $this->fail('إيقاف بلا سبب يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $stopped = (new StopAnnouncement)($published, $publisher, 'معلومة قديمة', 'D-2');
        $this->assertSame('stopped', $stopped->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'services.announcement_stopped', 'reason' => 'معلومة قديمة']);

        try {
            $stopped->delete();
            $this->fail('حذف إعلان يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $scheduled = $create(
            $creator, 'ANN-2', 'إعلان مجدول', 'نص', 'ar', 'institution',
            null, now()->addDays(2)->toDateTimeString(),
        );
        $scheduled = (new PublishAnnouncement)($scheduled, $publisher, 'D-3');
        $this->assertSame('scheduled', $scheduled->status);

        try {
            (new ActivateScheduledAnnouncement)($scheduled, $publisher);
            $this->fail('تفعيل إعلان قبل موعده يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $this->assertSame(2, Announcement::count());
        AuditLog::query()->where('action', 'services.announcement_created')->firstOrFail();
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
