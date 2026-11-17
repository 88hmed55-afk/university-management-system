<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_section_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 24)->default('draft');
            $table->text('notes')->nullable();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['course_section_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_submissions');
    }
};
