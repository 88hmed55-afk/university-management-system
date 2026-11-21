<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('run_no', 64)->unique();
            $table->foreignId('report_definition_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('correlation_id', 64)->nullable();
            $table->json('filters');
            $table->json('scope_snapshot');
            $table->unsignedInteger('row_count');
            $table->string('checksum', 64);
            $table->string('status', 24)->default('completed');
            $table->text('failure_reason')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['report_definition_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
    }
};
