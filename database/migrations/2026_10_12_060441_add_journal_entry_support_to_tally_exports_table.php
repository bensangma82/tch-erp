<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tally_exports', function (Blueprint $table) {
            $table->foreignId('finance_voucher_id')
                ->nullable()
                ->change();

            $table->foreignId('journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->unique(
                'journal_entry_id',
                'tally_exports_journal_entry_id_unique'
            );
        });

        DB::statement(
            'ALTER TABLE tally_exports
             ADD CONSTRAINT tally_exports_exactly_one_source_check
             CHECK (
                 (finance_voucher_id IS NOT NULL AND journal_entry_id IS NULL)
                 OR
                 (finance_voucher_id IS NULL AND journal_entry_id IS NOT NULL)
             )'
        );
    }

    public function down(): void
    {
        // Never silently discard journal export tracking records.
        if (
            DB::table('tally_exports')
                ->whereNotNull('journal_entry_id')
                ->exists()
        ) {
            throw new RuntimeException(
                'Cannot roll back: journal export records exist.'
            );
        }

        DB::statement(
            'ALTER TABLE tally_exports
             DROP CONSTRAINT IF EXISTS tally_exports_exactly_one_source_check'
        );

        Schema::table('tally_exports', function (Blueprint $table) {
            $table->dropUnique(
                'tally_exports_journal_entry_id_unique'
            );

            $table->dropForeign([
                'journal_entry_id',
            ]);

            $table->dropColumn('journal_entry_id');

            $table->foreignId('finance_voucher_id')
                ->nullable(false)
                ->change();
        });
    }
};