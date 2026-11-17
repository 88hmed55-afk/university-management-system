<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_policy_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_policy_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_item_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 16, 2);
            $table->decimal('discount_amount', 16, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fee_policy_id', 'fee_item_id'], 'fee_policy_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_policy_items');
    }
};
