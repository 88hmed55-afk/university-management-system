<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_no', 64)->unique();
            $table->foreignId('postgraduate_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('research_proposal_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('location', 200)->nullable();
            $table->string('committee_reference', 128)->nullable();
            $table->foreignId('minutes_document_id')->nullable()->constrained('documents')->restrictOnDelete();
            $table->text('recommendation')->nullable();
            $table->string('requirements_reference', 128);
            $table->string('status', 24)->default('scheduled');
            $table->foreignId('acted_by')->constrained('users')->restrictOnDelete();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->index(['postgraduate_record_id', 'status'], 'defense_session_record_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_sessions');
    }
};
