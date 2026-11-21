<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 64)->unique();
            $table->foreignId('service_request_type_id')->constrained()->restrictOnDelete();
            $table->string('requester_type', 128);
            $table->unsignedBigInteger('requester_id');
            $table->text('description');
            $table->string('status', 24)->default('submitted');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('workflow_version_no')->nullable();
            $table->unsignedInteger('current_step_no')->nullable();
            $table->string('channel_code', 32)->default('internal');
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['service_request_type_id', 'status'], 'service_request_type_status_index');
            $table->index(['requester_type', 'requester_id'], 'service_request_requester_index');
            $table->index(['status', 'assigned_to'], 'service_request_assignee_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
