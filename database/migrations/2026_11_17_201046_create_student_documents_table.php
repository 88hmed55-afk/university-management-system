<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->string('type_code', 64)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'document_id'], 'student_document_unique');
            $table->index(['status', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
