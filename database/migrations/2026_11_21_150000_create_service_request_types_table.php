<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('status', 24)->default('active');
            $table->unsignedInteger('current_workflow_version_no')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_types');
    }
};
