<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_no');
            $table->unsignedInteger('step_no');
            $table->string('name', 150);
            $table->string('role_code', 64);
            $table->string('scope_code', 64);
            $table->text('transition_condition')->nullable();
            $table->string('status', 24)->default('draft');
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->unique(
                ['service_request_type_id', 'version_no', 'step_no'],
                'workflow_step_version_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
