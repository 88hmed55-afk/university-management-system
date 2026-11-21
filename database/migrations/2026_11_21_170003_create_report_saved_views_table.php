<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_saved_views', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 128);
            $table->foreignId('report_definition_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->json('filters');
            $table->json('scope_snapshot');
            $table->string('status', 24)->default('active');
            $table->timestamps();

            $table->unique(['actor_id', 'report_definition_id', 'name'], 'report_saved_view_owner_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_saved_views');
    }
};
