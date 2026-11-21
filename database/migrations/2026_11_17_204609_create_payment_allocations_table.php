<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 16, 2);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->index(['student_payment_id', 'student_invoice_id'], 'payment_allocation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
