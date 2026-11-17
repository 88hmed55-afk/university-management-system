<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_program_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_term_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('intake_code', 64)->nullable();
            $table->string('status', 24)->default('active');
            $table->string('decision_reference', 128)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_id', 'academic_program_id', 'academic_year_id'],
                'student_program_intake_unique'
            );
            $table->index(['academic_program_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_program_enrollments');
    }
};
