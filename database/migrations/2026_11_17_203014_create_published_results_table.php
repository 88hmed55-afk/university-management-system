<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('published_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_section_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_enrollment_id')->constrained()->restrictOnDelete();
            $table->json('payload');
            $table->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at');
            $table->string('decision_reference', 128);
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('withdrawal_reason')->nullable();
            $table->timestamps();

            $table->unique(['course_section_id', 'course_enrollment_id'], 'published_result_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('published_results');
    }
};
