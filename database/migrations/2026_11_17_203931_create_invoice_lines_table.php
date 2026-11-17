<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_item_id')->constrained()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->decimal('amount', 16, 2);
            $table->decimal('discount_amount', 16, 2)->nullable();
            $table->timestamps();

            $table->unique(['student_invoice_id', 'fee_item_id'], 'invoice_line_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
