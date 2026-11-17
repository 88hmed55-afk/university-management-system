<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_program_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_no');
            $table->string('name')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status', 24)->default('draft');
            $table->string('decision_reference', 128)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['academic_program_id', 'version_no'], 'program_version_no_unique');
            $table->index(['status', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_versions');
    }
};
