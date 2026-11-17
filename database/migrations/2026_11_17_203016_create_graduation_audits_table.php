<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('study_plan_version_id')->constrained()->restrictOnDelete();
            $table->json('payload');
            $table->string('decision_reference', 128);
            $table->foreignId('audited_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('audited_at');
            $table->timestamps();

            $table->index(['student_id', 'audited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_audits');
    }
};
