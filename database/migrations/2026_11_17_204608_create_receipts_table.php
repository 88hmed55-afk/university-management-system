<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 64)->unique();
            $table->foreignId('student_payment_id')->unique()->constrained()->restrictOnDelete();
            $table->json('payload');
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
