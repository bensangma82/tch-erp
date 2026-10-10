<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            $table->string('journal_no', 50)->unique();
            $table->date('journal_date');

            /*
            | Source transaction
            */
            $table->string('source_type', 100);
            $table->string('source_id', 100);
            $table->string('source_event', 50)->default('posted');

            /*
            | Existing Finance Voucher
            */
            $table->foreignId('finance_voucher_id')
                ->nullable()
                ->constrained('finance_vouchers')
                ->restrictOnDelete();

            /*
            | Transaction details
            */
            $table->string('reference_no', 100)->nullable();
            $table->text('narration')->nullable();

            /*
            | Posting status
            */
            $table->string('status', 20)->default('draft');

            /*
            | Reversal tracking
            */
            $table->foreignId('reversal_of_id')
                ->nullable()
                ->constrained('journal_entries')
                ->restrictOnDelete();

            /*
            | Audit trail
            */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('posted_at')->nullable();

            $table->timestamps();

            /*
            | Duplicate prevention
            */
            $table->unique(
                ['source_type', 'source_id', 'source_event'],
                'journal_source_unique'
            );

            $table->unique(
                'reversal_of_id',
                'journal_reversal_unique'
            );

            $table->index('journal_date');
            $table->index('status');
            $table->index('finance_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};