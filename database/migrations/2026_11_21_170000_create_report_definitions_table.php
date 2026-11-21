<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('source_code', 64);
            $table->text('metric_definition');
            $table->string('metric_reference', 128);
            $table->string('scope_code', 24);
            $table->json('allowed_role_codes');
            $table->json('allowed_export_role_codes');
            $table->json('filter_definition');
            $table->unsignedInteger('max_rows');
            $table->string('max_rows_reference', 128);
            $table->string('freshness_code', 24)->default('live');
            $table->string('freshness_reference', 128)->nullable();
            $table->boolean('include_drafts')->default(false);
            $table->string('include_drafts_reference', 128)->nullable();
            $table->string('status', 24)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('decision_reference', 128);
            $table->timestamp('archived_at')->nullable();
            $table->text('archive_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'source_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_definitions');
    }
};
