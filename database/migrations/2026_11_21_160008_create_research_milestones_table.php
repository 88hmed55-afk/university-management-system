<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_milestones', function (Blueprint $table) {
            $table->id();
            $table->string('milestone_no', 64)->unique();
            $table->foreignId('postgraduate_record_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->text('report_note')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('supervisor_evaluation')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('planned');
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->index(['postgraduate_record_id', 'status'], 'research_milestone_record_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_milestones');
    }
};
