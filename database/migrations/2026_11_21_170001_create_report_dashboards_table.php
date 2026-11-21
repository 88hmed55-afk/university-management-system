<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_dashboards', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('role_code', 64);
            $table->string('scope_code', 24);
            $table->json('definition_ids');
            $table->string('decision_reference', 128);
            $table->string('status', 24)->default('active');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->text('archive_reason')->nullable();
            $table->timestamps();

            $table->index(['role_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_dashboards');
    }
};
