<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64);
            $table->unsignedInteger('version_no');
            $table->string('channel_code', 32);
            $table->string('subject', 200);
            $table->text('body');
            $table->string('status', 24)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->unique(['code', 'version_no'], 'notification_template_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
