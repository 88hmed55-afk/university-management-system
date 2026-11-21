<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postgraduate_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_no', 64)->unique();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained('academic_programs')->restrictOnDelete();
            $table->string('degree_code', 64);
            $table->string('admission_reference', 128)->nullable();
            $table->string('status', 24)->default('active');
            $table->string('decision_reference', 128)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'program_id'], 'postgraduate_student_program_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postgraduate_records');
    }
};
