<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_proposals', function (Blueprint $table) {
            $table->id();
            $table->string('proposal_no', 64)->unique();
            $table->foreignId('postgraduate_record_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_no');
            $table->string('title', 250);
            $table->text('abstract');
            $table->foreignId('document_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->unique(['postgraduate_record_id', 'version_no'], 'research_proposal_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_proposals');
    }
};
