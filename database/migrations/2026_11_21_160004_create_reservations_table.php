<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_no', 64)->unique();
            $table->foreignId('bibliographic_record_id')->constrained()->restrictOnDelete();
            $table->string('beneficiary_type', 128);
            $table->unsignedBigInteger('beneficiary_id');
            $table->string('status', 24)->default('requested');
            $table->timestamp('expires_at')->nullable();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->foreignId('acted_by')->constrained('users')->restrictOnDelete();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->index(['bibliographic_record_id', 'status'], 'reservation_record_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
