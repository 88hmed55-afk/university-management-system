<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no', 64)->unique();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('original_payment_id')->nullable()->constrained('student_payments')->restrictOnDelete();
            $table->foreignId('original_invoice_id')->nullable()->constrained('student_invoices')->restrictOnDelete();
            $table->string('direction', 16);
            $table->decimal('amount', 16, 2);
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_adjustments');
    }
};
