<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_audits', function (Blueprint $table) {
            $table->id();

            $table->string('audit_no')->unique();

            $table->date('audit_date');

            /*
             * draft
             * counting
             * review
             * approved
             * posted
             * cancelled
             */
            $table->string('status')->default('draft');

            $table->string('audit_type')->default('full');

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('posted_at')->nullable();

            $table->timestamps();

            $table->index('audit_date');
            $table->index('status');
            $table->index('audit_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_audits');
    }
};