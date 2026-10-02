<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_ledger_mappings', function (Blueprint $table) {
            $table->id();

            /*
             * Exactly one of these should normally be populated:
             *
             * finance_head_id    -> Income / Expense ledger
             * finance_account_id -> Cash / Bank ledger
             */
            $table->foreignId('finance_head_id')
                ->nullable()
                ->constrained('finance_heads')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('finance_account_id')
                ->nullable()
                ->constrained('finance_accounts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * Exact ledger name as configured in Tally.
             *
             * Examples:
             * OPD / Consultation
             * Laboratory
             * Main Cash
             * SBI Current
             */
            $table->string('tally_ledger_name', 200);

            /*
             * Optional Tally parent/group.
             *
             * Examples:
             * Sales Accounts
             * Indirect Expenses
             * Cash-in-Hand
             * Bank Accounts
             */
            $table->string('tally_group_name', 200)
                ->nullable();

            /*
             * Allows mapping to be temporarily disabled without
             * deleting historical configuration.
             */
            $table->boolean('is_active')
                ->default(true);

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * A Finance Head or Finance Account should only have
             * one Tally mapping.
             */
            $table->unique(
                'finance_head_id',
                'tally_mapping_finance_head_unique'
            );

            $table->unique(
                'finance_account_id',
                'tally_mapping_finance_account_unique'
            );

            $table->index(
                ['is_active', 'tally_ledger_name'],
                'tally_mapping_active_ledger_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_ledger_mappings');
    }
};