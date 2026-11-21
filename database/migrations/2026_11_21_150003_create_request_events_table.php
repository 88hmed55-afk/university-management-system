<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->restrictOnDelete();
            $table->string('event_type', 64);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->timestamps();

            $table->index(['service_request_id', 'event_type'], 'request_event_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_events');
    }
};
