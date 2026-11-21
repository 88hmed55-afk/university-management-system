<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bibliographic_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_no', 64)->unique();
            $table->string('title', 250);
            $table->string('author', 200)->nullable();
            $table->string('classification_code', 128)->nullable();
            $table->string('isbn', 32)->nullable()->unique();
            $table->string('language_code', 16);
            $table->string('publisher', 200)->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->foreignId('library_branch_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('active');
            $table->string('decision_reference', 128)->nullable();
            $table->timestamps();

            $table->index(['library_branch_id', 'status'], 'bibliographic_branch_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bibliographic_records');
    }
};
