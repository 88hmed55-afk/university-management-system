<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('announcement_no', 64)->unique();
            $table->string('title', 200);
            $table->text('body');
            $table->string('locale', 12)->default('ar');
            $table->string('audience_scope_code', 32);
            $table->string('scope_key', 128)->nullable();
            $table->string('status', 24)->default('draft');
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->index(['status', 'publish_at'], 'announcement_status_publish_index');
            $table->index(['audience_scope_code', 'scope_key'], 'announcement_audience_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
