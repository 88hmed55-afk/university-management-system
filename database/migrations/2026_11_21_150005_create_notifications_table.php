<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_no', 64)->unique();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('notification_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel_code', 32);
            $table->string('subject', 200);
            $table->text('body');
            $table->string('audience_scope_code', 32)->nullable();
            $table->string('action_url', 512)->nullable();
            $table->string('status', 24)->default('unread');
            $table->timestamp('read_at')->nullable();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->string('correlation_id', 64)->nullable();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'status'], 'notification_recipient_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
