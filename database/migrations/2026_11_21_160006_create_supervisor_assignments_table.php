<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postgraduate_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('role_code', 64);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('status', 24)->default('nominated');
            $table->text('reason')->nullable();
            $table->foreignId('nominated_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->index(['postgraduate_record_id', 'role_code'], 'supervisor_record_role_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_assignments');
    }
};
