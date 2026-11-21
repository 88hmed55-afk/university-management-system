<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('attempt_no');
            $table->string('channel_code', 32);
            $table->string('provider_code', 64)->nullable();
            $table->string('status', 24);
            $table->text('error')->nullable();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'attempt_no'], 'delivery_attempt_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_attempts');
    }
};
