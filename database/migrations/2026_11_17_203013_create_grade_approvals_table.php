<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_submission_id')->constrained()->restrictOnDelete();
            $table->string('decision', 24);
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->string('decision_reference', 128);
            $table->timestamps();

            $table->index('grade_submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_approvals');
    }
};
