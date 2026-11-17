<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained()->restrictOnDelete();
            $table->string('code', 64);
            $table->unsignedInteger('capacity')->nullable();
            $table->foreignId('campus_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['course_offering_id', 'code'], 'course_section_code_unique');
            $table->index('campus_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sections');
    }
};
