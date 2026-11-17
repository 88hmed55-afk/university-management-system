<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 64)->unique();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_policy_id')->constrained()->restrictOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_term_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('draft');
            $table->decimal('total_amount', 16, 2);
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->string('decision_reference', 128)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_invoices');
    }
};
