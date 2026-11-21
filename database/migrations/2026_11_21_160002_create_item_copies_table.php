<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_copies', function (Blueprint $table) {
            $table->id();
            $table->string('copy_no', 64)->unique();
            $table->foreignId('bibliographic_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('library_branch_id')->constrained()->restrictOnDelete();
            $table->string('location_code', 128)->nullable();
            $table->string('status', 24)->default('available');
            $table->date('acquired_at')->nullable();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->index(['bibliographic_record_id', 'status'], 'item_copy_record_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_copies');
    }
};
