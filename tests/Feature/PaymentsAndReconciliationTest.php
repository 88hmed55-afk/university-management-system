<?php

namespace Tests\Feature;

use App\Actions\AllocatePaymentToInvoice;
use App\Actions\ApproveCashSessionClosure;
use App\Actions\ApproveRefund;
use App\Actions\CaptureStudentPayment;
use App\Actions\CloseCashSession;
use App\Actions\ExecuteRefund;
use App\Actions\IssueReceipt;
use App\Actions\OpenCashSession;
use App\Actions\RecordBankReconciliation;
use App\Actions\RequestRefund;
use App\Models\AuditLog;
use App\Models\BankReconciliation;
use App\Models\Cashbox;
use App\Models\Currency;
use App\Models\FinancialAdjustment;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
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

class PaymentsAndReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_payment_schema_is_created(): void
    {
        foreach ([
            'cashboxes', 'cash_sessions', 'student_payments', 'receipts',
            'payment_allocations', 'refunds', 'financial_adjustments', 'bank_reconciliations',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('student_payments', [
            'payment_no', 'student_id', 'amount', 'currency_id',
            'external_reference', 'idempotency_key', 'status',
        ]));
        $this->assertTrue(Schema::hasColumns('refunds', [
            'refund_no', 'original_payment_id', 'amount', 'reason', 'status',
        ]));
    }

    public function test_capture_is_idempotent_audited_and_guarded(): void
    {
        $cashier = $this->userWithRole(RoleCode::Cashier);
        $student = Student::factory()->create();
        $currency = Currency::factory()->create();
        $capture = new CaptureStudentPayment;

        $payment = $capture(
            $student, $cashier, '100.00', $currency->id, 'cash', 'PAY-1',
            'EXT-1', null, null, 'IDEM-1',
        );

        $this->assertSame('captured', $payment->status);
        $this->assertSame('100.00', $payment->amount);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finance.payment_captured', 'actor_id' => $cashier->id,
        ]);

        $again = $capture(
            $student, $cashier, '100.00', $currency->id, 'cash', 'PAY-2',
            'EXT-2', null, null, 'IDEM-1',
        );
        $this->assertSame($payment->id, $again->id);

        try {
            $capture($student, $cashier, '100.00', $currency->id, 'cash', 'PAY-3', 'EXT-1');
            $this->fail('تكرار مرجع الدفع الخارجي يجب أن يُرفض.');
        } catch (QueryException) {
            // متوقع
        }

        try {
            $capture($student, $cashier, '0', $currency->id, 'cash', 'PAY-4');
            $this->fail('دفعة بقيمة صفر يجب أن تُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        try {
            $payment->delete();
            $this->fail('حذف حركة القبض يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $outsider = $this->userWithRole(RoleCode::CampusManager);

        try {
            $capture($student, $outsider, '10', $currency->id, 'cash', 'PAY-5');
            $this->fail('يجب رفض التحصيل من غير مخول.');
        } catch (AuthorizationException) {
            // متوقع
        }
    }

    public function test_receipt_is_immutable_snapshot_one_per_payment(): void
    {
        $cashier = $this->userWithRole(RoleCode::Cashier);
        $payment = StudentPayment::factory()->create();
        $issue = new IssueReceipt;

        $receipt = $issue($payment, $cashier, 'RCP-1');

        $this->assertSame($payment->payment_no, $receipt->payload['payment_no']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.receipt_issued', 'actor_id' => $cashier->id]);

        $again = $issue($payment, $cashier, 'RCP-2');
        $this->assertSame($receipt->id, $again->id);
        $this->assertSame(1, Receipt::count());

        try {
            $receipt->update(['receipt_no' => 'RCP-X']);
            $this->fail('تعديل الإيصال يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $receipt->delete();
            $this->fail('حذف الإيصال يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_allocation_requires_matching_currency_and_prevents_over_allocation(): void
    {
        $cashier = $this->userWithRole(RoleCode::Cashier);
        $currency = Currency::factory()->create();
        $otherCurrency = Currency::factory()->create();
        $student = Student::factory()->create();
        $payment = StudentPayment::factory()->create([
            'student_id' => $student->id, 'currency_id' => $currency->id, 'amount' => '100.00',
        ]);
        $invoice = StudentInvoice::factory()->create([
            'student_id' => $student->id, 'currency_id' => $currency->id,
            'total_amount' => '140.00', 'status' => 'issued',
        ]);
        $allocate = new AllocatePaymentToInvoice;

        $first = $allocate($payment, $invoice, $cashier, '50.00');
        $this->assertSame('50.00', $first->amount);

        try {
            $allocate($payment, $invoice, $cashier, '60.00');
            $this->fail('تجاوز المتبقي من الدفعة يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $otherInvoice = StudentInvoice::factory()->create([
            'student_id' => $student->id, 'currency_id' => $otherCurrency->id,
            'total_amount' => '50.00', 'status' => 'issued',
        ]);

        try {
            $allocate($payment, $otherInvoice, $cashier, '10.00');
            $this->fail('اختلاف العملة يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $second = $allocate($payment, $invoice, $cashier, '40.00');
        $this->assertSame('40.00', $second->amount);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.payment_allocated', 'actor_id' => $cashier->id]);
    }

    public function test_refund_lifecycle_enforces_separation_and_links_original_movement(): void
    {
        $requester = $this->userWithRole(RoleCode::FinanceDirector);
        $approver = $this->userWithRole(RoleCode::FinancialLeadership);
        $student = Student::factory()->create();
        $payment = StudentPayment::factory()->create([
            'student_id' => $student->id, 'amount' => '100.00',
        ]);

        try {
            (new RequestRefund)($payment, $requester, '150.00', 'سبب', 'RFD-0');
            $this->fail('رد يتجاوز الدفعة يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $refund = (new RequestRefund)($payment, $requester, '30.00', 'خطأ في التحصيل', 'RFD-1');

        try {
            (new ApproveRefund)($refund, $requester, 'DEC-1');
            $this->fail('لا يمكن لفاعل واحد طلب واعتماد الرد.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $approved = (new ApproveRefund)($refund, $approver, 'DEC-1');
        $this->assertSame('approved', $approved->status);

        $adjustment = (new ExecuteRefund)($approved, $approver, 'ADJ-1');

        $this->assertSame('credit', $adjustment->direction);
        $this->assertSame($payment->id, $adjustment->original_payment_id);
        $this->assertSame('executed', $refund->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'finance.refund_executed', 'actor_id' => $approver->id]);
        $this->assertSame(1, FinancialAdjustment::count());

        try {
            $adjustment->delete();
            $this->fail('حذف التسوية يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }
    }

    public function test_cash_session_lifecycle_requires_difference_reason_and_supervisor_approval(): void
    {
        $cashier = $this->userWithRole(RoleCode::Cashier);
        $supervisor = $this->userWithRole(RoleCode::FinancialLeadership);
        $currency = Currency::factory()->create();
        $cashbox = Cashbox::factory()->create(['currency_id' => $currency->id]);

        $session = (new OpenCashSession)($cashbox, $cashier);
        $this->assertSame('open', $session->status);

        try {
            (new OpenCashSession)($cashbox, $cashier);
            $this->fail('تكرار وردية مفتوحة يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        (new CaptureStudentPayment)(
            Student::factory()->create(), $cashier, '100.00', $currency->id,
            'cash', 'PAY-S1', null, $cashbox, $session,
        );

        try {
            (new CloseCashSession)($session, $cashier, '90.00');
            $this->fail('فرق بدون تفسير يجب أن يُرفض.');
        } catch (InvalidArgumentException) {
            // متوقع
        }

        $closed = (new CloseCashSession)($session, $cashier, '90.00', 'فرق جرد');

        $this->assertSame('100.00', $closed->expected_amount);
        $this->assertSame('90.00', $closed->actual_amount);
        $this->assertDatabaseHas('audit_logs', ['action' => 'cash.session_closed', 'actor_id' => $cashier->id]);

        try {
            (new ApproveCashSessionClosure)($closed, $cashier, 'DEC-1');
            $this->fail('لا يمكن لأمين الصندوق اعتماد إقفال ورديته.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $approved = (new ApproveCashSessionClosure)($closed, $supervisor, 'DEC-1');
        $this->assertNotNull($approved->approved_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'cash.session_closure_approved', 'actor_id' => $supervisor->id,
        ]);
    }

    public function test_bank_reconciliation_computes_difference_and_is_immutable(): void
    {
        $supervisor = $this->userWithRole(RoleCode::FinancialLeadership);
        $cashbox = Cashbox::factory()->create();
        $record = new RecordBankReconciliation;

        $reconciliation = $record(
            $cashbox, $supervisor, '2040-01-01', '2040-01-31',
            '100.00', '92.50', 'REC-1', 'فرق إيداع بنكي',
        );

        $this->assertSame('-7.50', $reconciliation->difference_amount);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finance.bank_reconciliation_recorded', 'actor_id' => $supervisor->id,
        ]);

        try {
            $reconciliation->update(['actual_amount' => '100.00']);
            $this->fail('تعديل المطابقة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $reconciliation->delete();
            $this->fail('حذف المطابقة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->assertSame(1, BankReconciliation::count());
        AuditLog::query()->where('action', 'finance.bank_reconciliation_recorded')->firstOrFail();
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
