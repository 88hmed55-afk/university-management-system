<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table): void {
            $table->id();
            $table->string('export_no', 64)->unique();
            $table->foreignId('report_definition_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('format_code', 16);
            $table->string('storage_key', 255)->nullable();
            $table->string('checksum', 64)->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('max_rows');
            $table->string('limit_reference', 128);
            $table->json('filters');
            $table->json('scope_snapshot');
            $table->boolean('include_pii')->default(false);
            $table->string('pii_basis_reference', 128)->nullable();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->string('status', 24)->default('completed');
            $table->text('failure_reason')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['report_definition_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
