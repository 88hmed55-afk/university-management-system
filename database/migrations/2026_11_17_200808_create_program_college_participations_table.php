<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_college_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_program_id')->constrained()->restrictOnDelete();
            $table->foreignId('college_id')->constrained()->restrictOnDelete();
            $table->string('participation_type_code', 64)->nullable();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('decision_reference', 128);
            $table->string('status', 24)->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['academic_program_id', 'college_id', 'valid_from'],
                'program_participation_validity_unique'
            );
            $table->index(['college_id', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_college_participations');
    }
};
