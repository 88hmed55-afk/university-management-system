<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_section_id')->constrained()->restrictOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->string('type_code', 64)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->string('status', 24)->default('active');
            $table->string('decision_reference', 128)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['course_section_id', 'code'], 'assessment_component_code_unique');
            $table->index('course_section_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_components');
    }
};
