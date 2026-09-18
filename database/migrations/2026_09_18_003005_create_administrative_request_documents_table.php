<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_request_documents', function (Blueprint $table) {

            $table->id();

            $table->foreignId('administrative_request_id')
                ->constrained('administrative_requests')
                ->cascadeOnDelete();

            $table->string('document_type', 100)->nullable();

            $table->string('title', 255);

            $table->string('file_name', 255);

            $table->string('file_path', 500);

            $table->string('mime_type', 150)->nullable();

            $table->unsignedBigInteger('file_size')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('uploaded_at')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('administrative_request_id');
            $table->index('document_type');
            $table->index('uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_request_documents');
    }
};