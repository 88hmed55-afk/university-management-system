<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('level_code', 64)->nullable();
            $table->foreignId('primary_college_id')->constrained('colleges')->restrictOnDelete();
            $table->string('status', 24)->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['primary_college_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_programs');
    }
};
