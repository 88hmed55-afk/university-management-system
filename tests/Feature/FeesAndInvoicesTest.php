<?php

namespace Tests\Feature;

use App\Actions\ApproveFeePolicy;
use App\Actions\CancelStudentInvoice;
use App\Actions\CreateFeePolicy;
use App\Actions\CreateStudentInvoice;
use App\Actions\DefineCurrency;
use App\Actions\DefineFeeItem;
use App\Actions\IssueStudentInvoice;
use App\Actions\PublishFeePolicy;
use App\Models\Currency;
use App\Models\FeeItem;
use App\Models\FeePolicy;
use App\Models\FeePolicyItem;
use App\Models\InvoiceLine;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\User;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class FeesAndInvoicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_fee_schema_is_created(): void
    {
        foreach ([
            'currencies', 'fee_items', 'fee_policies', 'fee_policy_items',
            'student_invoices', 'invoice_lines',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('fee_policies', [
            'code', 'version_no', 'currency_id', 'effective_from', 'effective_to', 'status', 'created_by',
        ]));
        $this->assertTrue(Schema::hasColumns('student_invoices', [
            'invoice_no', 'student_id', 'fee_policy_id', 'currency_id',
            'total_amount', 'idempotency_key', 'status', 'cancelled_at',
        ]));
        $this->assertTrue(Schema::hasColumns('invoice_lines', [
            'student_invoice_id', 'fee_item_id', 'amount', 'discount_amount',
        ]));
    }

    public function test_define_currency_and_fee_item_are_audited_and_guarded(): void
    {
        $actor = $this->userWithRole(RoleCode::FinanceDirector);

        $currency = (new DefineCurrency)($actor, 'CURX', 'عملة اختبار', 'DEC-1');
        $item = (new DefineFeeItem)($actor, 'FI-X', 'بند اختبار');

        $this->assertSame('active', $currency->status);
        $this->assertSame('active', $item->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.currency_defined', 'actor_id' => $actor->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.fee_item_defined', 'actor_id' => $actor->id]);

        $outsider = $this->userWithRole(RoleCode::CampusManager);

        try {
            (new DefineCurrency)($outsider, 'CURY', 'عملة');
            $this->fail('يجب رفض تعريف عملة من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }

        try {
            $currency->delete();
            $this->fail('حذف العملة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $currency->update(['code' => 'CURZ']);
            $this->fail('تعديل رمز العملة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_create_fee_policy_snapshots_items_without_invented_values(): void
    {
        $actor = $this->userWithRole(RoleCode::FinanceDirector);
        $currency = Currency::factory()->create();
        $itemA = FeeItem::factory()->create();
        $itemB = FeeItem::factory()->create();
        $create = new CreateFeePolicy;

        $policy = $create(
            $actor, 'FP-1', 1, 'سياسة اختبار', $currency->id,
            '2040-01-01', '2040-12-31',
            [
                ['fee_item_id' => $itemA->id, 'amount' => '100.00'],
                ['fee_item_id' => $itemB->id, 'amount' => '50.00', 'discount_amount' => '10.00'],
            ],
            null, null, 'DEC-1',
        );

        $this->assertSame('draft', $policy->status);
        $this->assertSame(2, $policy->items()->count());
        $this->assertNull($policy->items()->where('fee_item_id', $itemA->id)->first()->discount_amount);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.fee_policy_created', 'actor_id' => $actor->id]);

        try {
            $create(
                $actor, 'FP-1', 1, 'مكرر', $currency->id,
                '2040-01-01', null,
                [['fee_item_id' => $itemA->id, 'amount' => '10']],
            );
            $this->fail('تكرار (رمز، إصدار) السياسة يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        try {
            $create(
                $actor, 'FP-2', 1, 'خصم أكبر', $currency->id,
                '2040-01-01', null,
                [['fee_item_id' => $itemA->id, 'amount' => '10', 'discount_amount' => '20']],
            );
            $this->fail('خصم يتجاوز المبلغ يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }
    }

    public function test_policy_approval_enforces_separation_and_publish_requires_approval(): void
    {
        $creator = $this->userWithRole(RoleCode::FinanceDirector);
        $approver = $this->userWithRole(RoleCode::FinancialLeadership);
        $policy = FeePolicy::factory()->create(['status' => 'draft', 'created_by' => $creator->id]);

        try {
            (new PublishFeePolicy)($policy, $creator, 'PUB-1');
            $this->fail('لا يمكن نشر سياسة غير معتمدة.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            (new ApproveFeePolicy)($policy, $creator, 'DEC-1');
            $this->fail('لا يمكن لفاعل واحد الإنشاء والاعتماد.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $approved = (new ApproveFeePolicy)($policy, $approver, 'DEC-1');

        $this->assertSame('approved', $approved->status);
        $this->assertSame($approver->id, $approved->approved_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.fee_policy_approved', 'actor_id' => $approver->id]);

        $published = (new PublishFeePolicy)($approved, $creator, 'PUB-1');

        $this->assertSame('published', $published->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.fee_policy_published', 'actor_id' => $creator->id]);

        try {
            $published->update(['name' => 'تعديل']);
            $this->fail('تعديل السياسة المنشورة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_invoice_is_created_from_published_policy_with_snapshot_and_idempotency(): void
    {
        $actor = $this->userWithRole(RoleCode::FinanceDirector);
        $policy = FeePolicy::factory()->create(['status' => 'published']);
        FeePolicyItem::factory()->create([
            'fee_policy_id' => $policy->id,
            'amount' => '100.00',
            'discount_amount' => '10.00',
        ]);
        FeePolicyItem::factory()->create([
            'fee_policy_id' => $policy->id,
            'amount' => '50.00',
        ]);
        $student = Student::factory()->create();
        $create = new CreateStudentInvoice;

        $draftPolicy = FeePolicy::factory()->create(['status' => 'draft']);

        try {
            $create($student, $draftPolicy, $actor, 'INV-1');
            $this->fail('لا يمكن الفوترة من سياسة غير منشورة.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $invoice = $create($student, $policy, $actor, 'INV-1', null, 'KEY-1');

        $this->assertSame('140.00', $invoice->total_amount);
        $this->assertSame('draft', $invoice->status);
        $this->assertSame($policy->currency_id, $invoice->currency_id);
        $this->assertSame(2, $invoice->lines()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.invoice_created', 'actor_id' => $actor->id]);

        $again = $create($student, $policy, $actor, 'INV-2', null, 'KEY-1');
        $this->assertSame($invoice->id, $again->id);

        try {
            $create($student, $policy, $actor, 'INV-3');
            $this->fail('تكرار الفاتورة للطالب والسياسة يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            StudentInvoice::factory()->create(['invoice_no' => 'INV-1']);
            $this->fail('تكرار رقم الفاتورة يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }
    }

    public function test_invoice_issue_and_cancel_are_audited_with_separation_of_duties(): void
    {
        $creator = $this->userWithRole(RoleCode::FinanceDirector);
        $other = $this->userWithRole(RoleCode::FinancialLeadership);
        $policy = FeePolicy::factory()->create(['status' => 'published']);
        $invoice = StudentInvoice::factory()->create([
            'status' => 'draft', 'fee_policy_id' => $policy->id, 'created_by' => $creator->id,
        ]);
        InvoiceLine::factory()->create(['student_invoice_id' => $invoice->id]);

        $issued = (new IssueStudentInvoice)($invoice, $creator);

        $this->assertSame('issued', $issued->status);
        $this->assertNotNull($issued->issued_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.invoice_issued', 'actor_id' => $creator->id]);

        try {
            $issued->lines()->first()->update(['amount' => '999']);
            $this->fail('تعديل بند فاتورة مُصدرة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            (new CancelStudentInvoice)($issued, $creator, 'سبب');
            $this->fail('لا يمكن لفاعل واحد الإنشاء والإلغاء.');
        } catch (AuthorizationException) {
            // متوقع
        }

        try {
            (new CancelStudentInvoice)($issued, $other, '   ');
            $this->fail('سبب الإلغاء مطلوب.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $cancelled = (new CancelStudentInvoice)($issued, $other, 'خطأ في الاستحقاق');

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.invoice_cancelled', 'actor_id' => $other->id]);

        try {
            $cancelled->delete();
            $this->fail('حذف الفاتورة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        // رقم الفاتورة الملغاة لا يُعاد استخدامه
        try {
            StudentInvoice::factory()->create(['invoice_no' => $invoice->invoice_no]);
            $this->fail('إعادة استخدام رقم فاتورة ملغاة يجب أن تُرفض.');
        } catch (QueryException) {
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
