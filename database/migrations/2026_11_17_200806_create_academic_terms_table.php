<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->string('term_type_code', 64)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_open_at')->nullable();
            $table->date('registration_close_at')->nullable();
            $table->date('exam_start_at')->nullable();
            $table->date('exam_end_at')->nullable();
            $table->string('status', 24)->default('active');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'code'], 'academic_term_year_code_unique');
            $table->index(['academic_year_id', 'start_date', 'end_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};
