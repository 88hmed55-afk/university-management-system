<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_plan_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('course_type_code', 64)->nullable();
            $table->decimal('credits', 6, 2)->nullable();
            $table->string('level_code', 64)->nullable();
            $table->string('status', 24)->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['study_plan_version_id', 'course_id'], 'study_plan_course_unique');
            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plan_courses');
    }
};
