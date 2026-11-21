<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no', 64)->unique();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashbox_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('amount', 16, 2);
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->string('method_code', 64);
            $table->string('external_reference', 128)->nullable()->unique();
            $table->timestamp('payment_date');
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('captured');
            $table->string('decision_reference', 128)->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index('cash_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};
