<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('person_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->foreignId('identifier_type_id')->constrained('person_identifier_types')->restrictOnDelete();
            $table->text('value');
            $table->string('value_hash', 64);
            $table->string('issuing_authority')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 24)->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->unique(['identifier_type_id', 'value_hash'], 'person_identifier_value_unique');
            $table->index('person_id');
            $table->index(['person_id', 'effective_to']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('person_identifiers');
    }
};
