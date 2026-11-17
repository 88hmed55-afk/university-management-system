<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('study_plan_version_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('payload');
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('generated_at');
            $table->string('decision_reference', 128);
            $table->timestamps();

            $table->index(['student_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transcripts');
    }
};
