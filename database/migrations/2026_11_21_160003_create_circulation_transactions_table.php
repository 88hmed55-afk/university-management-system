<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circulation_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no', 64)->unique();
            $table->foreignId('item_copy_id')->constrained()->restrictOnDelete();
            $table->string('beneficiary_type', 128);
            $table->unsignedBigInteger('beneficiary_id');
            $table->string('transaction_type', 24);
            $table->foreignId('original_transaction_id')->nullable()->constrained('circulation_transactions')->restrictOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('occurred_at');
            $table->text('condition_note')->nullable();
            $table->foreignId('acted_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('recorded');
            $table->string('decision_reference', 128)->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamps();

            $table->index(['beneficiary_type', 'beneficiary_id'], 'circulation_beneficiary_index');
            $table->index(['item_copy_id', 'transaction_type'], 'circulation_copy_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circulation_transactions');
    }
};
