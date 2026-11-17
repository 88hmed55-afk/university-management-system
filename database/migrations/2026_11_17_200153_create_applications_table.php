<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_cycle_id')->constrained()->restrictOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->string('application_no', 64)->unique();
            $table->string('program_code', 64)->nullable();
            $table->string('idempotency_key', 128)->unique();
            $table->string('status', 24)->default('submitted');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['admission_cycle_id', 'status']);
            $table->index('person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
