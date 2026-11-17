<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->restrictOnDelete();
            $table->string('decision', 24);
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at');
            $table->string('reference', 128);
            $table->text('reason')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamps();

            $table->index(['application_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_decisions');
    }
};
