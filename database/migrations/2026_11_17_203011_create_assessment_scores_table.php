<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('assessment_component_id')->constrained()->restrictOnDelete();
            $table->decimal('raw_score', 8, 2)->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['course_enrollment_id', 'assessment_component_id'],
                'assessment_score_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_scores');
    }
};
