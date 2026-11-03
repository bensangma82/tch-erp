<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhis_legacy_settlements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mhis_opening_receivable_id')
                ->constrained('mhis_opening_receivables')
                ->restrictOnDelete();

            $table->date('settlement_date');

            $table->string('settlement_reference', 255);
            $table->string('historical_claim_reference', 255)->nullable();

            $table->decimal('net_amount', 18, 2)->default(0);
            $table->decimal('tds_amount', 18, 2)->default(0);
            $table->decimal('approved_deduction', 18, 2)->default(0);
            $table->decimal('gross_settled_amount', 18, 2);

            $table->string('status', 30)->default('draft');
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->foreignId('journal_entry_id')
                ->nullable()
                ->unique()
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('settlement_reference');
            $table->index('historical_claim_reference');
            $table->index(['mhis_opening_receivable_id', 'status'],
                'mhis_legacy_opening_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhis_legacy_settlements');
    }
};
