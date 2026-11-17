<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_policies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64);
            $table->unsignedInteger('version_no');
            $table->string('name');
            $table->foreignId('academic_program_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 24)->default('draft');
            $table->string('decision_reference', 128)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['code', 'version_no'], 'fee_policy_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_policies');
    }
};
