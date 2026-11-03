<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhis_opening_receivables', function (Blueprint $table) {
            $table->id();

            $table->date('opening_date')->unique();
            $table->string('financial_year', 9);

            $table->decimal('opening_amount', 18, 2);
            $table->string('statement_reference')->nullable();
            $table->text('remarks')->nullable();

            $table->string('status', 30)->default('draft');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('journal_entry_id')
                ->nullable()
                ->unique()
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhis_opening_receivables');
    }
};
